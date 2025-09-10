<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of courses
     */
    public function index()
    {
        $courses = Course::with(['chapters.lessons', 'chapters.quiz.questions'])->get();
        
        return response()->json([
            'courses' => CourseResource::collection($courses)
        ]);
    }

    /**
     * Display the specified course
     */
    public function show($id)
    {
        $course = Course::with(['chapters.lessons', 'chapters.quiz.questions'])->find($id);
        
        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }
        
        return response()->json([
            'course' => new CourseResource($course)
        ]);
    }

    /**
     * Store a newly created course
     */
    public function store(CourseRequest $request)
    {
        $course = Course::create($request->validated());

        return response()->json([
            'message' => 'Course created successfully',
            'course' => new CourseResource($course)
        ], 201);
    }

    /**
     * Update the specified course
     */
    public function update(CourseRequest $request, $id)
    {
        $course = Course::find($id);
        
        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        $course->update($request->validated());

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => new CourseResource($course)
        ]);
    }

    /**
     * Remove the specified course
     */
    public function destroy($id)
    {
        $course = Course::find($id);
        
        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }
}