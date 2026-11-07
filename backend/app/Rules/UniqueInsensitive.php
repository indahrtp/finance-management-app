<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueInsensitive implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column,
        private ?int $ignoreId = null,
    ) {}
    
    public static function clean(mixed $value): mixed
    {
        return is_string($value) ? preg_replace('/\s+/u', ' ', trim($value)) : $value;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existing = DB::table($this->table)
            ->whereRaw("LOWER({$this->column}) = LOWER(?)", [$value])
            ->when($this->ignoreId, fn ($q) => $q->where('id', '!=', $this->ignoreId))
            ->value($this->column);

        if ($existing !== null) {
            $fail("Sudah ada \"{$existing}\". Huruf besar dan kecil dianggap sama.");
        }
    }
}