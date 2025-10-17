<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedCourse;
use App\Models\Course;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeaturedCourseController extends Controller
{
    // List featured courses
    // public function index(Request $request)
    // {
    //     $perPage = (int)($request->get('per_page', 15));
    //     $search = $request->get('search');
    //     $sort = $request->get('sort');
    //     $field = $request->get('field');

    //     $query = FeaturedCourse::query()->with('course');
    //     $query->search($search)->field($field)->sort($sort);

    //     $featuredCourses = $query->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'data' => $featuredCourses,
    //     ]);
    // }

    // Show single
    // public function show(FeaturedCourse $featuredCourse)
    // {
    //     return response()->json([
    //         'success' => true,
    //         'data' => $featuredCourse->load('course'),
    //     ]);
    // }

    // Create featured course (admin only)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id', Rule::unique('featured_courses', 'course_id')],
            'priority' => ['nullable', 'integer'],
            // 'featured_at' => ['nullable', 'date'],
            // 'expires_at' => ['nullable', 'date', 'after_or_equal:featured_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $validated['is_active'] ?? true;
        $featured = FeaturedCourse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course featured successfully',
            'data' => $featured->load('course'),
        ], 201);
    }

    // Delete
    public function destroy(FeaturedCourse $featuredCourse)
    {
        $featuredCourse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Featured course deleted successfully',
        ]);
    }
}