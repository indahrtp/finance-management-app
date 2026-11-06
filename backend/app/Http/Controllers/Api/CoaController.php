<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Coa::with('category')->orderBy('code');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn ($q) => $q->where('code', 'ilike', $s)->orWhere('name', 'ilike', $s));
        }

        return $query->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:coa,code'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        return response()->json(Coa::create($data)->load('category'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Coa $coa)
    {
        return $coa->load('category');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Coa $coa)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('coa', 'code')->ignore($coa->id)],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $coa->update($data);

        return $coa->load('category');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coa $coa)
    {
        if ($coa->transactions()->exists()) {
            return response()->json(['message' => 'COA sudah dipakai di transaksi dan tidak bisa dihapus.'], 409);
        }

        $coa->delete();

        return response()->noContent();
    }
}