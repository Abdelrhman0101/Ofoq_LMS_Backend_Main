<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;



class CourseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Course::class, 'course');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = Course::query()
            ->search($request->input('search'))
            ->field($request->input('field'))
            // ->level($request->input('level'))
            // ->language($request->input('language'))
            ->sort($request->input('sort'))
            ->paginate(12);

        return response()->json([
            'message' => 'Courses fetched successfully.',
            'data' => CourseResource::collection($courses),
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseRequest $request)
    {
        $validated = $request->validated();

        // لو الكورس مجاني، نخلي السعر صفر
        if (!empty($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = 0;
            $validated['discount_price'] = null;
        }

        // إنشاء الكورس الجديد
        $course = Course::create($validated);

        return response()->json([
            'message' => 'Course created successfully.',
            'data' => new CourseResource($course),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        return response()->json([
            'message' => 'Course retrieved successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseRequest $request, Course $course)
    {
        $validated = $request->validated();

        // لو الكورس مجاني → السعر صفر
        if (!empty($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = 0;
            $validated['discount_price'] = null;
        }

        // تحديث الكورس
        $course->update($validated);

        return response()->json([
            'message' => 'Course updated successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully.'
        ], 204);
    }
}
