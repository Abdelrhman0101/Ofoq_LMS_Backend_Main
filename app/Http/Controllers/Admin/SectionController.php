<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::orderBy('display_order')->get();
        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:sections,slug',
            'icon' => 'nullable|string',
            'is_published' => 'boolean',
            'display_order' => 'nullable|integer',
        ], [
            'slug.unique' => 'عفواً، هذا الرابط (slug) مستخدم بالفعل، يرجى اختيار رابط آخر.',
        ]);

        if (isset($validated['slug']) && empty($validated['slug'])) {
            unset($validated['slug']);
        }

        $section = Section::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'data' => $section
        ], 201);
    }

    public function show(Section $section)
    {
        return response()->json([
            'success' => true,
            'data' => $section
        ]);
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|unique:sections,slug,' . $section->id,
            'icon' => 'nullable|string',
            'is_published' => 'boolean',
            'display_order' => 'nullable|integer',
        ], [
            'slug.unique' => 'عفواً، هذا الرابط (slug) مستخدم بالفعل، يرجى اختيار رابط آخر.',
        ]);

        $section->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully.',
            'data' => $section
        ]);
    }

    public function destroy(Section $section)
    {
        // Nullify section_id in related courses and categories is handled by onDelete('set null')
        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully.'
        ]);
    }
}
