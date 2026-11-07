<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coa;
use App\Rules\UniqueInsensitive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $request->merge(['name' => UniqueInsensitive::clean($request->input('name'))]);

        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', new UniqueInsensitive('coa', 'name')],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $coa = DB::transaction(function () use ($data) {
            DB::select('SELECT pg_advisory_xact_lock(?)', [73001]); 

            $category = Category::findOrFail($data['category_id']);

            return Coa::create([
                'name' => $data['name'],
                'category_id' => $category->id,
                'code' => $this->generateCode($category),
            ]);
        });

        return response()->json($coa->load('category'), 201);
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
        $request->merge(['name' => UniqueInsensitive::clean($request->input('name'))]);

        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:255', new UniqueInsensitive('coa', 'name', $coa->id)],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $newCategory = Category::findOrFail($data['category_id']);

        if ($newCategory->type !== $coa->category->type) {
            throw ValidationException::withMessages([
                'category_id' => "Kategori harus bertipe {$coa->category->type->value}. Untuk pindah tipe, buat COA baru.",
            ]);
        }

        $coa->update($data); // kode tidak berubah

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

    public function suggestCode(Request $request)
    {
        $request->validate(['category_id' => ['required', 'exists:categories,id']]);

        return ['code' => $this->generateCode(Category::findOrFail($request->category_id))];
    }

    private function generateCode(Category $category): string
    {
        $prefix = $category->type->codePrefix(); // '4' atau '6'

        $max = DB::table('coa')
            ->whereRaw('code ~ ?', ["^{$prefix}[0-9]{2}$"])
            ->max(DB::raw('CAST(code AS INTEGER)'));

        $next = $max ? $max + 1 : (int) ($prefix . '01');

        if ($next > (int) ($prefix . '99')) {
            throw ValidationException::withMessages([
                'category_id' => "Rentang kode {$prefix}xx sudah penuh.",
            ]);
        }

        return (string) $next;
    }
}