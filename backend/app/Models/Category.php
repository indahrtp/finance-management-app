<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'type'];

    protected $casts = ['type' => CategoryType::class];

    public function coa(): HasMany
    {
        return $this->hasMany(Coa::class);
    }
}