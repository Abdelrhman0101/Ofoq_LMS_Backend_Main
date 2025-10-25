<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryOfCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * List all categories (diplomas) with course counts.
     */
    public function index()
    {
        $categories = CategoryOfCourse::query()
            ->withCount('courses')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Create a new category (diploma) including optional cover image.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:category_of_course,name',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|unique:category_of_course,slug',
            'is_published' => 'sometimes|boolean',
            'is_free' => 'sometimes|boolean',
            'price' => 'nullable|numeric|min:0',
            'cover_image' => 'nullable|image|max:20480', // up to ~20MB
            'display_order' => 'nullable|integer|min:0',
        ]);

        if (!empty($validated['is_free'])) {
            $validated['price'] = 0;
        }

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('categories/cover_images', 'public');
            $validated['cover_image'] = $path;
        }

        $category = CategoryOfCourse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Update an existing category (diploma) including optional cover image replacement.
     */
    public function update(Request $request, $id)
    {
        $category = CategoryOfCourse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:category_of_course,name,' . $category->id,
            'description' => 'nullable|string',
            'slug' => 'nullable|string|unique:category_of_course,slug,' . $category->id,
            'is_published' => 'sometimes|boolean',
            'is_free' => 'sometimes|boolean',
            'price' => 'nullable|numeric|min:0',
            'cover_image' => 'nullable|image|max:20480',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if (!empty($validated['is_free'])) {
            $validated['price'] = 0;
        }

        if ($request->hasFile('cover_image')) {
            if ($category->cover_image && Storage::disk('public')->exists($category->cover_image)) {
                Storage::disk('public')->delete($category->cover_image);
            }
            $path = $request->file('cover_image')->store('categories/cover_images', 'public');
            $validated['cover_image'] = $path;
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    /**
     * Show a single category (diploma).
     */
    public function show($id)
    {
        $category = CategoryOfCourse::query()
            ->withCount('courses')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Delete a category (diploma) and its cover image; courses will be detached via FK onDelete(set null).
     */
    public function destroy($id)
    {
        $category = CategoryOfCourse::findOrFail($id);

        if ($category->cover_image && Storage::disk('public')->exists($category->cover_image)) {
            Storage::disk('public')->delete($category->cover_image);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
