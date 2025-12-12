<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;




class CourseController extends Controller
{
    // Authorization is handled by middleware in routes/api.php



    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 10));

        $courses = Course::withoutGlobalScope('published')
            ->with([
            'chapters.lessons.quiz',
            'reviews.user',
            'instructor',
        ])
            // Order by rank ascending, NULLs last, then by id
            ->orderByRaw('`rank` IS NULL')
            ->orderBy('rank')
            ->orderBy('id')
            ->paginate($perPage);
        $courses->loadCount(['chapters', 'lessons', 'reviews']);
        $courses->loadAvg('reviews', 'rating');
        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses->items()),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
            ]
        ]);
    }

    public function store(CourseRequest $request)
    {
        Log::info('CourseController@store: Request received to create a new course.');
        
        // Debug logging
        Log::info('Course creation request received', [
            'request_data' => $request->all(),
            'files' => $request->allFiles(),
            'timestamp' => now()
        ]);
        
        try {
            $validated = $request->validated();
            Log::info('Validation passed', ['validated_data' => $validated]);

            // Extract chapters data before creating course (allow raw JSON from FormData)
            $chaptersData = $request->input('chapters', []);
            if (is_string($chaptersData)) {
                $decoded = json_decode($chaptersData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $chaptersData = $decoded;
                } else {
                    Log::warning('Invalid chapters JSON provided', ['chapters' => $chaptersData]);
                    $chaptersData = [];
                }
            }
            if (!is_array($chaptersData)) {
                $chaptersData = [];
            }
            unset($validated['chapters']); // Remove from course data
            
            // Remove status field if present (frontend compatibility)
            if (isset($validated['status'])) {
                unset($validated['status']);
            }

            if (!empty($validated['is_free']) && $validated['is_free']) {
                $validated['price'] = 0;
                $validated['discount_price'] = null;
            }
            if (empty($validated['is_published'])) {
                $validated['is_published'] = false;
            }
            
            // Debug: Log all uploaded files
            Log::info('Checking for cover_image upload', [
                'hasFile' => $request->hasFile('cover_image'),
                'allFiles' => array_keys($request->allFiles()),
                'cover_image_in_request' => $request->has('cover_image'),
                'cover_image_value' => $request->input('cover_image'),
            ]);
            
            // IMPORTANT: Remove cover_image from validated data first to prevent saving false/0
            // We will only add it back if a valid file is uploaded
            unset($validated['cover_image']);
            
            if ($request->hasFile('cover_image')) {
                $image = $request->file('cover_image');
                
                // Validate the file is actually valid
                if ($image->isValid()) {
                    Log::info('Cover image file details', [
                        'originalName' => $image->getClientOriginalName(),
                        'mimeType' => $image->getClientMimeType(),
                        'size' => $image->getSize(),
                    ]);
                    
                    $path = $image->store('courses/cover_images', 'public');
                    
                    if ($path) {
                        $validated['cover_image'] = $path;
                        Log::info('Cover image stored successfully', ['path' => $path]);
                    } else {
                        Log::error('Failed to store cover image - store() returned false');
                    }
                } else {
                    Log::warning('Cover image file is not valid', [
                        'error' => $image->getError(),
                        'errorMessage' => $image->getErrorMessage(),
                    ]);
                }
            } else {
                Log::info('No cover_image file in request - this is normal if user did not select an image');
            }

            Log::info('About to create course', ['data' => $validated]);
            $course = Course::create($validated);
            Log::info('Course created successfully', ['course_id' => $course->id, 'course_title' => $course->title]);

            // Create chapters and lessons if provided
            if (!empty($chaptersData)) {
                foreach ($chaptersData as $chapterData) {
                    Log::info('Creating chapter', ['chapter_data' => $chapterData]);
                    
                    // Extract lessons data before creating chapter
                    $lessonsData = $chapterData['lessons'] ?? [];
                    unset($chapterData['lessons']); // Remove from chapter data
                    
                    // Create chapter
                    $chapter = $course->chapters()->create([
                        'title' => $chapterData['title'],
                        'description' => $chapterData['description'] ?? null,
                        'order' => $chapterData['order'] ?? 1,
                    ]);
                    
                    Log::info('Chapter created successfully', ['chapter_id' => $chapter->id, 'chapter_title' => $chapter->title]);
                    
                    // Create lessons for this chapter if provided
                    if (!empty($lessonsData)) {
                        foreach ($lessonsData as $lessonData) {
                            Log::info('Creating lesson', ['lesson_data' => $lessonData]);
                            
                            $lesson = $chapter->lessons()->create([
                                'title' => $lessonData['title'],
                                'content' => $lessonData['content'] ?? '',
                                'order' => $lessonData['order'] ?? 1,
                                'video_url' => $lessonData['video_url'] ?? null,
                                'resources' => $lessonData['resources'] ?? [],
                                'is_visible' => $lessonData['is_visible'] ?? true,
                            ]);
                            
                            Log::info('Lesson created successfully', ['lesson_id' => $lesson->id, 'lesson_title' => $lesson->title]);
                        }
                    }
                }
            }

            // Load all relationships for the response
            $course->load(['instructor', 'chapters.lessons']);

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully.',
                'data' => new CourseResource($course),
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Course creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Course creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        $course = Course::withoutGlobalScope('published')
            ->with([
                'chapters.lessons.quiz.questions',
                'reviews.user',
                'instructor',
            ])
            ->find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.'
            ], 404);
        }

        $course->loadCount(['chapters', 'lessons', 'reviews']);
        $course->loadAvg('reviews', 'rating');

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course),
        ]);
    }


    /**
     * تعديل كورس
     */
    public function update(CourseRequest $request, Course $course)
    {
        $validated = $request->validated();

        if (!empty($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = 0;
            $validated['discount_price'] = null;
        }

        // IMPORTANT: Remove cover_image from validated data to prevent overwriting with false/0
        // We only update it if a valid new file is uploaded
        unset($validated['cover_image']);

        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            
            if ($image->isValid()) {
                // Delete old image if exists
                if ($course->cover_image && Storage::disk('public')->exists($course->cover_image)) {
                    Storage::disk('public')->delete($course->cover_image);
                }
                
                $path = $image->store('courses/cover_images', 'public');
                
                if ($path) {
                    $validated['cover_image'] = $path;
                    Log::info('Cover image updated successfully', ['path' => $path]);
                } else {
                    Log::error('Failed to store cover image during update');
                }
            } else {
                Log::warning('Cover image file is not valid during update', [
                    'error' => $image->getError(),
                ]);
            }
        }

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.',
            'data' => new CourseResource($course),
        ]);
    }


    public function destroy(Course $course)
    {
        // احذف الصورة من التخزين لو موجودة
        if ($course->cover_image && Storage::disk('public')->exists($course->cover_image)) {
            Storage::disk('public')->delete($course->cover_image);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.',
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     $perPage = (int) ($request->query('per_page', 10));
    //     $courses = Course::with(['chapters.lessons', 'chapters.quiz.questions'])
    //         ->paginate($perPage);

    //     return response()->json([
    //         'data' => CourseResource::collection($courses->items()),
    //         'pagination' => [
    //             'current_page' => $courses->currentPage(),
    //             'per_page' => $courses->perPage(),
    //             'total' => $courses->total(),
    //             'last_page' => $courses->lastPage(),
    //         ]
    //     ]);
    // }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(CourseRequest $request)
    // {
    //     $validated = $request->validated();

    //     if (!empty($validated['is_free']) && $validated['is_free']) {
    //         $validated['price'] = 0;
    //         $validated['discount_price'] = null;
    //     }

    //     $course = Course::create($validated);

    //     return response()->json([
    //         'message' => 'Course created successfully.',
    //         'data' => new CourseResource($course),
    //     ], 201);
    // }

    /**
     * Display the specified resource.
     */
    // public function show($id)
    // {
    //     $course = Course::with(['chapters.lessons', 'chapters.quiz.questions'])->find($id);

    //     if (!$course) {
    //         return response()->json([
    //             'message' => 'Course not found'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'course' => new CourseResource($course)
    //     ]);
    // }


    /**
     * Update the specified resource in storage.
     */
    // public function update(CourseRequest $request, Course $course)
    // {
    //     $validated = $request->validated();

    //     if (!empty($validated['is_free']) && $validated['is_free']) {
    //         $validated['price'] = 0;
    //         $validated['discount_price'] = null;
    //     }

    //     $course->update($validated);

    //     return response()->json([
    //         'message' => 'Course updated successfully.',
    //         'data' => new CourseResource($course),
    //     ]);
    // }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Course $course)
    // {
    //     $course->delete();

    //     return response()->json([
    //         'message' => 'Course deleted successfully.'
    //     ], 204);
    // }


    public function details()
    {
        $totalCourses = Course::withoutGlobalScope('published')->count();
        $totalStudents = \App\Models\UserCourse::distinct('user_id')->count();
        $publishedCourses = Course::where('is_published', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_courses' => $totalCourses,
                'total_students' => $totalStudents,
                'published_courses' => $publishedCourses,
            ]
        ]);
    }

    public function getOnlyCoursesNotPublished()
    {
        $courses = Course::withoutGlobalScope('published')
            ->where('is_published', false)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }
}
