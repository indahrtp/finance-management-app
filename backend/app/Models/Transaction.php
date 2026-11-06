<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['date', 'coa_id', 'user_id', 'description', 'debit', 'credit'];

    protected $casts = [
        'date'   => 'date:Y-m-d',
        'debit'  => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}