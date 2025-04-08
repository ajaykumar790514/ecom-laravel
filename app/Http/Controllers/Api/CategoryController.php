<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('directChildren')->where('parent_id', 0)->get();
        return response()->json($categories, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'slug' => 'nullable|string|unique:categories,slug',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            if ($parent && $parent->parent_id != 0) {
                return response()->json(['error' => 'Only two levels of categories allowed (parent and child).'], 400);
            }
        }

        $existing = Category::where('title', $request->title)
                        ->where('parent_id', $request->parent_id ?? 0)
                        ->first();

        if ($existing) {
            return response()->json(['error' => 'A category with this title already exists under the selected parent.'], 409);
        }

        $category = Category::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->image,
            'slug' => $request->slug ?? Str::slug($request->title),
            'parent_id' => $request->parent_id ?? 0,
            'seq' => $request->seq ?? 0,
        ]);

        return response()->json(['message' => 'Category created', 'data' => $category], 201);
    }

    public function show($id)
    {
        $category = Category::with('directChildren')->find($id);

        if (!$category) {
            return response()->json(['error' => 'Category not found.'], 404);
        }

        return response()->json($category, 200);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found.'], 404);
        }

        $request->validate([
            'title' => 'sometimes|required|string',
            'slug' => 'nullable|string|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        if ($request->has('parent_id')) {
            $newParent = Category::find($request->parent_id);
            if ($newParent && $newParent->parent_id != 0) {
                return response()->json(['error' => 'Only two levels allowed.'], 400);
            }
        }

        $title = $request->title ?? $category->title;
        $parent_id = $request->parent_id ?? $category->parent_id;

        $exists = Category::where('title', $title)
                          ->where('parent_id', $parent_id)
                          ->where('id', '!=', $id)
                          ->first();

        if ($exists) {
            return response()->json(['error' => 'Another category with the same title exists under the same parent.'], 409);
        }

        $category->update([
            'title' => $title,
            'description' => $request->description ?? $category->description,
            'image' => $request->image ?? $category->image,
            'slug' => $request->slug ?? $category->slug,
            'parent_id' => $parent_id,
            'seq' => $request->seq ?? $category->seq,
        ]);

        return response()->json(['message' => 'Category updated', 'data' => $category], 200);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found.'], 404);
        }

        foreach ($category->directChildren as $child) {
            $child->delete();
        }

        $category->delete();

        return response()->json(['message' => 'Category and its children deleted'], 200);
    }

    public function tree()
    {
        return response()->json(
            Category::with('directChildren')->where('parent_id', 0)->get(),
            200
        );
    }
}
