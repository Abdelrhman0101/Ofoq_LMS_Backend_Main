<?php

namespace App\Http\Controllers;

use App\Models\CategoryOfCourse;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryDetailsResource;
use Illuminate\Http\Request;

class DiplomaController extends Controller
{
    /**
     * List published diplomas (categories) with basic filters.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $search = $request->input('search');
        $sort = $request->input('sort'); // 'latest' | 'oldest' | 'order'

        $query = CategoryOfCourse::query()
            ->where('is_published', true)
            ->when(!empty($search), function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->withCount('courses');

        if ($sort === 'latest') {
            $query->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderBy('display_order')->orderBy('name');
        }

        $diplomas = $query->paginate($perPage);

        return CategoryResource::collection($diplomas)
            ->additional(['message' => 'Diplomas fetched successfully.']);
    }
    /**
     * عرض تفاصيل الدبلوم (التصنيف) عبر الـ slug أو id مع دوراته.
     */
    public function show($slug, Request $request)
    {
        $diploma = CategoryOfCourse::query()
            ->where('is_published', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if (is_numeric($slug)) {
                    $q->orWhere('id', (int) $slug);
                }
            })
            ->with(['courses' => function ($q) {
                $q->where('is_published', true)
                  ->withCount(['chapters', 'reviews'])
                  ->withAvg('reviews', 'rating')
                  ->with('instructor')
                  ->with('category');
            }])
            ->first();

        if (!$diploma) {
            return response()->json([
                'success' => false,
                'message' => 'Diploma not found.'
            ], 404);
        }

        return (new CategoryDetailsResource($diploma))
            ->additional(['message' => 'Diploma retrieved successfully.']);
    }
}