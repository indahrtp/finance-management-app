<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Salary', 'income'],
            ['Other Income', 'income'],
            ['Family Expense', 'expense'],
            ['Transport Expense', 'expense'],
            ['Meal Expense', 'expense'],
        ] as [$name, $type]) {
            Category::updateOrCreate(['name' => $name], ['type' => $type]);
        }
    }
}