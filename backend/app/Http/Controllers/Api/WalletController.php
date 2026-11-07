<?php

namespace App\Http\Controllers\Api;

use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Rules\UniqueInsensitive;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Wallet::withBalance()->withCount('transactions')->orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge(['name' => UniqueInsensitive::clean($request->input('name'))]);

        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:100', new UniqueInsensitive('wallets', 'name')],
            'type' => ['required', Rule::enum(WalletType::class)],
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
        ]);
        $data['opening_balance'] ??= 0;

        $wallet = Wallet::create($data);

        return response()->json($this->fresh($wallet->id), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Wallet $wallet)
    {
        return $this->fresh($wallet->id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wallet $wallet)
    {
        $request->merge(['name' => UniqueInsensitive::clean($request->input('name'))]);

        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'max:100', new UniqueInsensitive('wallets', 'name', $wallet->id)],
            'type' => ['required', Rule::enum(WalletType::class)],
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
        ]);
        $data['opening_balance'] ??= 0;

        $wallet->update($data);

        return $this->fresh($wallet->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wallet $wallet)
    {
        if ($wallet->transactions()->exists()) {
            return response()->json(['message' => 'Rekening/dompet sudah dipakai di transaksi dan tidak bisa dihapus.'], 409);
        }

        $wallet->delete();

        return response()->noContent();
    }

    private function fresh(int $id)
    {
        return Wallet::withBalance()->withCount('transactions')->findOrFail($id);
    }
}
