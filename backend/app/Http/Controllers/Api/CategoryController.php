<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Rules\UniqueInsensitive;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::withCount('coa')->orderBy('type')->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge(['name' => UniqueInsensitive::clean($request->input('name'))]);

        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', new UniqueInsensitive('categories', 'name')],
            'type' => ['required', Rule::enum(CategoryType::class)],
        ]);

        return response()->json(Category::create($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return $category->load('coa');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->merge(['name' => UniqueInsensitive::clean($request->input('name'))]);

        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', new UniqueInsensitive('categories', 'name', $category->id)],
            'type' => ['required', Rule::enum(CategoryType::class)],
        ]);

        if ($category->type->value !== $data['type'] && $category->coa()->exists()) {
            throw ValidationException::withMessages([
                'type' => 'Tipe tidak dapat diubah karena kategori ini sudah memiliki COA.',
            ]);
        }

        $category->update($data);

        return $category;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->coa()->exists()) {
            return response()->json(['message' => 'Kategori masih dipakai oleh COA dan tidak bisa dihapus.'], 409);
        }

        $category->delete();

        return response()->noContent();
    }
}