<?php

namespace App\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function codePrefix(): string
    {
        return match ($this) {
            self::Income => '4',
            self::Expense => '6',
        };
    }
}