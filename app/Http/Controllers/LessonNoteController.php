<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\UserCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonNoteController extends Controller
{
    // List notes for a lesson for the authenticated user
    public function index($lessonId)
    {
        $user = Auth::user();
        $lesson = Lesson::findOrFail($lessonId);

        // Ensure user enrolled in the course of the lesson
        $courseId = $lesson->chapter->course_id;
        $enrolled = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to access lesson notes.',
            ], 403);
        }

        $notes = LessonNote::where('lesson_id', $lessonId)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
        ]);
    }

    // Create a note for a lesson for the authenticated user
    public function store(Request $request, $lessonId)
    {
        $user = Auth::user();
        $lesson = Lesson::findOrFail($lessonId);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        // Ensure user enrolled in the course of the lesson
        $courseId = $lesson->chapter->course_id;
        $enrolled = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to add lesson notes.',
            ], 403);
        }

        $note = LessonNote::create([
            'user_id' => $user->id,
            'lesson_id' => $lessonId,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully',
            'data' => $note,
        ], 201);
    }

    // Update user's note
    public function update(Request $request, $lessonId, $noteId)
    {
        $user = Auth::user();
        $lesson = Lesson::findOrFail($lessonId);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $note = LessonNote::where('id', $noteId)
            ->where('lesson_id', $lessonId)
            ->where('user_id', $user->id)
            ->first();

        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Note not found or you do not have permission to edit it.',
            ], 404);
        }

        $note->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully',
            'data' => $note,
        ]);
    }

    // Delete user's note
    public function destroy($lessonId, $noteId)
    {
        $user = Auth::user();
        $lesson = Lesson::findOrFail($lessonId);

        $note = LessonNote::where('id', $noteId)
            ->where('lesson_id', $lessonId)
            ->where('user_id', $user->id)
            ->first();

        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Note not found or you do not have permission to delete it.',
            ], 404);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully',
        ]);
    }
}