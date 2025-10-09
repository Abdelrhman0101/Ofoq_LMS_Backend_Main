<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class UserFavoriteCourseController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->favoriteCourses;
    }

    public function store(Request $request, Course $course)
    {
        $request->user()->favoriteCourses()->attach($course);

        return response()->json(['message' => 'Course added to favorites.']);
    }

    public function destroy(Request $request, Course $course)
    {
        $favoriteCourse = $course->favoriteByUsers()->where('user_id', $request->user()->id)->first();
        if (!$favoriteCourse) {
            return response()->json(['message' => 'Course not found in favorites.'], 404);
        }
        $request->user()->favoriteCourses()->detach($course);

        return response()->json(['message' => 'Course removed from favorites.']);
    }
}
