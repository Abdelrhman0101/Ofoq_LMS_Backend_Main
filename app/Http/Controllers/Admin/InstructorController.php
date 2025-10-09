<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InstructorController extends Controller
{
    // List instructors (with pagination)
    public function index(Request $request)
    {
        $perPage = (int)($request->get('per_page', 15));
        $instructors = Instructors::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $instructors,
        ]);
    }

    // Show single instructor
    public function show(Instructors $instructor)
    {
        $instructor->load([
            'courses' => function ($q) {
                $q->withCount(['chapters', 'lessons', 'reviews'])
                    ->withAvg('reviews', 'rating');
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $instructor,
        ]);
    }


    // Create instructor
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            // 'email' => ['nullable', 'email', 'max:255', Rule::unique('instructors', 'email')],
            'bio' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('instructors', 'public');
            $validated['image'] = $path;
        }

        $instructor = Instructors::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Instructor created successfully',
            'data' => $instructor,
        ], 201);
    }

    // Update instructor
    public function update(Request $request, Instructors $instructor)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('instructors', 'email')->ignore($instructor->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // delete old image if exists
            if (!empty($instructor->image)) {
                Storage::disk('public')->delete($instructor->image);
            }
            $path = $request->file('image')->store('instructors', 'public');
            $validated['image'] = $path;
        }

        $instructor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Instructor updated successfully',
            'data' => $instructor,
        ]);
    }

    // Delete instructor (cascade deletes courses via FK onDelete('cascade'))
    public function destroy(Instructors $instructor)
    {
        // delete image if exists
        if (!empty($instructor->image)) {
            Storage::disk('public')->delete($instructor->image);
        }
        $instructor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Instructor deleted successfully',
        ]);
    }
}
