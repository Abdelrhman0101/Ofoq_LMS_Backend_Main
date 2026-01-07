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
            ->when($request->has('section_id'), function ($q) use ($request) {
                $q->where('section_id', $request->input('section_id'));
            })
            ->with(['section', 'courses'])
            ->withCount('courses');

        if ($sort === 'latest') {
            $query->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByRaw('`display_order` IS NULL')
                  ->orderBy('display_order')
                  ->orderBy('id');
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
                  ->with('category')
                  // ترتيب المقررات حسب rank تصاعديًا مع وضع القيم NULL في النهاية
                  ->orderByRaw('`rank` IS NULL')
                  ->orderBy('rank')
                  ->orderBy('id');
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