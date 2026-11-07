<?php

namespace App\Models;

use App\Enums\WalletType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['name', 'type', 'opening_balance'];

    protected $casts = [
        'type' => WalletType::class,
        'opening_balance' => 'float',
        'balance' => 'float', 
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Saldo = saldo awal + (credit - debit) transaksi posted
    public function scopeWithBalance(Builder $query): Builder
    {
        return $query->select('wallets.*')->selectRaw(
            "wallets.opening_balance + COALESCE((
                SELECT SUM(t.credit - t.debit) FROM transactions t
                WHERE t.wallet_id = wallets.id AND t.status = 'posted'
            ), 0) AS balance"
        );
    }
}