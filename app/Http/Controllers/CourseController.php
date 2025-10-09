<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FeaturedCourse;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\FeaturedCourseResource;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of courses with pagination
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

    public function search(Request $request)
    {
        $courses = Course::query()
            ->search($request->input('search'))
            ->field($request->input('field'))
            ->sort($request->input('sort'))
            ->paginate(12);

        $courses->loadCount(['chapters', 'lessons', 'reviews']);
        $courses->loadAvg('reviews', 'rating');

        return CourseResource::collection($courses)
            ->additional([
                'message' => 'Courses fetched successfully.'
            ]);
    }


    /**
     * Display featured courses
     */
    // public function featured(Request $request)
    // {
    //     $search = $request->input('search');
    //     $field = $request->input('field');
    //     $sort = $request->input('sort');

    //     $featured = FeaturedCourse::query()
    //         ->where('is_active', true)
    //         ->search($search)
    //         ->field($field)
    //         ->sort($sort)
    //         ->orderBy('priority')
    //         ->get();

    //     $featured->loadCount(['chapters', 'lessons', 'reviews']);
    //     $featured->loadAvg('reviews', 'rating');
    //     return response()->json([
    //         'featured' => $featured->map(function ($fc) {
    //             return [
    //                 'priority' => $fc->priority,
    //                 'featured_at' => $fc->featured_at,
    //                 'course' => new CourseResource($fc->course),
    //             ];
    //         }),
    //     ]);
    // }

    public function featured(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $search = $request->input('search');
        $field = $request->input('field');
        $sort = $request->input('sort');

        $query = FeaturedCourse::query()
            ->where('is_active', true)
            ->search($search)
            ->field($field)
            ->sort($sort)
            ->orderBy('priority')
            ->with(['course' => function ($q) {
                $q->withCount(['chapters', 'reviews'])
                    ->withAvg('reviews', 'rating')
                    ->with('instructor');
            }]);

        $featured = $query->paginate($perPage);

        return FeaturedCourseResource::collection($featured)
            ->additional(['message' => 'Featured courses retrieved successfully.']);
    }

    /**
     * Display the specified course
     */

    public function show(Course $course)
    {
        $course->load(['chapters.lessons.quiz', 'reviews.user', 'instructor']);
        $course->loadCount(['chapters', 'lessons', 'reviews']);
        $course->loadAvg('reviews', 'rating');

        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Store a newly created course
     */
    // public function store(CourseRequest $request)
    // {
    //     $course = Course::create($request->validated());

    //     return response()->json([
    //         'message' => 'Course created successfully',
    //         'course' => new CourseResource($course)
    //     ], 201);
    // }

    /**
     * Update the specified course
     */
    // public function update(CourseRequest $request, $id)
    // {
    //     $course = Course::find($id);

    //     if (!$course) {
    //         return response()->json([
    //             'message' => 'Course not found'
    //         ], 404);
    //     }

    //     $course->update($request->validated());

    //     return response()->json([
    //         'message' => 'Course updated successfully',
    //         'course' => new CourseResource($course)
    //     ]);
    // }

    /**
     * Remove the specified course
     */
    // public function destroy($id)
    // {
    //     $course = Course::find($id);

    //     if (!$course) {
    //         return response()->json([
    //             'message' => 'Course not found'
    //         ], 404);
    //     }

    //     $course->delete();

    //     return response()->json([
    //         'message' => 'Course deleted successfully'
    //     ]);
    // }
}
