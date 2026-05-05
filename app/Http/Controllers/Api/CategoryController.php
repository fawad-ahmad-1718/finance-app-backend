<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $request->user()
            ->categories()
            ->withCount('transactions')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:100',
            'type'  => 'required|in:income,expense',
            'icon'  => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $category = $request->user()->categories()->create($validated);

        return response()->json($category, 201);
    }

    public function show(Request $request, Category $category)
    {
        $this->authorizeCategory($request, $category);
        return response()->json($category->load('transactions'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeCategory($request, $category);

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:100',
            'type'  => 'sometimes|in:income,expense',
            'icon'  => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorizeCategory($request, $category);

        if ($category->transactions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete a category that has transactions. Remove or reassign them first.',
            ], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }

    private function authorizeCategory(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }
    }
}
