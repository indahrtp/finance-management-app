<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::where('type', 'expense')->pluck('id', 'name');

        $today = Carbon::today();

        for ($m = 1; $m <= $today->month; $m++) {
            $period = Carbon::create($today->year, $m, 1)->toDateString();
            $semester = in_array($m, [1, 7], true);

            foreach ([
                'Family Expense' => $semester ? 4000000 : 1700000,
                'Transport Expense' => 900000,
                'Meal Expense' => 1600000,
            ] as $name => $amount) {
                Budget::updateOrCreate(
                    ['category_id' => $categories[$name], 'period' => $period],
                    ['amount' => $amount],
                );
            }
        }
    }
}