<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;




class CourseController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth'); // apply authentication middleware
    //     $this->authorizeResource(Course::class, 'course'); // apply resource authorization
    // }



    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 10));

        $courses = Course::with([
            'chapters.lessons.quiz',
            'reviews.user',
            'instructor',
        ])->paginate($perPage);
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
        $validated = $request->validated();

        if (!empty($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = 0;
            $validated['discount_price'] = null;
        }
        if (empty($validated['is_published'])) {
            $validated['is_published'] = false;
        }
        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            $path = $image->store('courses/cover_images', 'public');
            $validated['cover_image'] = $path;
        }

        $course = Course::create($validated);

        $course->load('instructor');

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'data' => new CourseResource($course),
        ], 201);
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

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image && Storage::disk('public')->exists($course->cover_image)) {
                Storage::disk('public')->delete($course->cover_image);
            }
            $image = $request->file('cover_image');
            $path = $image->store('courses/cover_images', 'public');
            $validated['cover_image'] = $path;
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
