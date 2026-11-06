<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    public function generate(int $year): array
    {
        // Agregasi di database: kategori x bulan
        $rows = DB::table('transactions as t')
            ->join('coa as a', 'a.id', '=', 't.coa_id')
            ->join('categories as c', 'c.id', '=', 'a.category_id')
            ->whereYear('t.date', $year)
            ->groupBy('c.id', DB::raw('EXTRACT(MONTH FROM t.date)'))
            ->selectRaw("
                c.id as category_id,
                EXTRACT(MONTH FROM t.date)::int as month,
                SUM(CASE WHEN c.type = 'income'
                         THEN t.credit - t.debit
                         ELSE t.debit - t.credit END) as amount
            ")
            ->get();

        // Index: [category_id][month] => amount
        $map = [];
        foreach ($rows as $r) {
            $map[$r->category_id][$r->month] = (float) $r->amount;
        }

        $categories = Category::orderBy('id')->get();

        $income = [];
        $expense = [];

        foreach ($categories as $cat) {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[] = $map[$cat->id][$m] ?? 0;
            }

            $line = [
                'category_id' => $cat->id,
                'category' => $cat->name,
                'months' => $months,
                'total' => array_sum($months),
            ];

            if ($cat->type->value === 'income') {
                $income[] = $line;
            } else {
                $expense[] = $line;
            }
        }

        $totalIncome = $this->sumLines($income);
        $totalExpense = $this->sumLines($expense);

        $netMonths = [];
        for ($m = 0; $m < 12; $m++) {
            $netMonths[] = $totalIncome['months'][$m] - $totalExpense['months'][$m];
        }

        return [
            'year' => $year,
            'months' => range(1, 12),
            'income' => $income,
            'expense' => $expense,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_income' => [
                'months' => $netMonths,
                'total' => array_sum($netMonths),
            ],
        ];
    }

    private function sumLines(array $lines): array
    {
        $months = array_fill(0, 12, 0);

        foreach ($lines as $line) {
            foreach ($line['months'] as $i => $value) {
                $months[$i] += $value;
            }
        }

        return ['months' => $months, 'total' => array_sum($months)];
    }
}