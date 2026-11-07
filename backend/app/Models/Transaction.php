<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'date', 'coa_id', 'wallet_id', 'user_id', 'reference_no', 'description',
        'notes', 'debit', 'credit', 'status', 'attachment_path', 'attachment_name',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'status' => TransactionStatus::class,
    ];
    
    protected $hidden = ['attachment_path'];
    protected $appends = ['has_attachment'];

    protected function hasAttachment(): Attribute
    {
        return Attribute::get(fn () => $this->attachment_path !== null);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Posted->value);
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}