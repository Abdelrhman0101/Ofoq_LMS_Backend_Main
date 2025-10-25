<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CategoryOfCourse;
use App\Models\Quiz;

class CategoryFinalExamController extends Controller
{
    /**
     * GET /api/admin/categories/{category}/final-exam
     * Return the existing final exam quiz for a category, or 404 if missing.
     */
    public function show(CategoryOfCourse $category)
    {
        $finalExam = $category->finalExam;
        if (!$finalExam) {
            return response()->json([
                'success' => false,
                'message' => 'Diploma final exam not found for this category.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $finalExam,
        ]);
    }

    /**
     * POST /api/admin/categories/{category}/final-exam
     * Create or update the final exam quiz for a category (diploma).
     */
    public function store(Request $request, CategoryOfCourse $category)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $finalExam = $category->finalExam;
        if ($finalExam) {
            $finalExam->update([
                'title' => $validated['title'],
            ]);
        } else {
            $finalExam = Quiz::create([
                'title' => $validated['title'],
                'is_final' => true,
                'quizzable_type' => CategoryOfCourse::class,
                'quizzable_id' => $category->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $finalExam,
        ]);
    }
}