<?php

namespace App\Http\Controllers;

use App\Models\CategoryOfCourse;
use App\Http\Resources\CategoryResource;
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
}