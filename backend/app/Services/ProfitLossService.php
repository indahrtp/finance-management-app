<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    private const AMOUNT = "CASE WHEN c.type = 'income' THEN t.credit - t.debit ELSE t.debit - t.credit END";

    private function base(): Builder
    {
        return DB::table('transactions as t')
            ->join('coa as a', 'a.id', '=', 't.coa_id')
            ->join('categories as c', 'c.id', '=', 'a.category_id')
            ->where('t.status', TransactionStatus::Posted->value);
    }

    public static function percentChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /* ---------- Tahunan: 12 kolom bulan ---------- */

    public function generate(int $year): array
    {
        $rows = $this->base()
            ->whereBetween('t.date', ["{$year}-01-01", "{$year}-12-31"])
            ->groupBy('c.id', DB::raw('EXTRACT(MONTH FROM t.date)'))
            ->selectRaw('c.id as category_id, EXTRACT(MONTH FROM t.date)::int as month, SUM(' . self::AMOUNT . ') as amount')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->category_id][$r->month] = (float) $r->amount;
        }

        $income = [];
        $expense = [];

        foreach (Category::orderBy('id')->get() as $cat) {
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

            if ($cat->type === CategoryType::Income) {
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
            'net_income' => ['months' => $netMonths, 'total' => array_sum($netMonths)],
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

    /* ---------- Bulanan: per kategori dan COA, dibanding bulan lalu ---------- */

    public function generateMonthly(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1, 0, 0, 0);
        $end = $start->copy()->endOfMonth();
        $prevStart = $start->copy()->subMonthNoOverflow();
        $s = $start->toDateString();
        $amount = self::AMOUNT;

        $byCategory = $this->base()
            ->whereBetween('t.date', [$prevStart->toDateString(), $end->toDateString()])
            ->groupBy('a.id', 'a.code', 'a.name', 'c.id')
            ->orderBy('a.code')
            ->selectRaw("a.id as coa_id, a.code, a.name as coa_name, c.id as category_id,
                SUM(CASE WHEN t.date >= ?::date THEN {$amount} ELSE 0 END) as current_amount,
                SUM(CASE WHEN t.date <  ?::date THEN {$amount} ELSE 0 END) as previous_amount", [$s, $s])
            ->get()
            ->groupBy('category_id');

        $income = [];
        $expense = [];

        foreach (Category::orderBy('id')->get() as $cat) {
            $accounts = $byCategory->get($cat->id, collect())
                ->map(function ($r) {
                    $cur = (float) $r->current_amount;
                    $prev = (float) $r->previous_amount;

                    return [
                        'coa_id' => $r->coa_id, 'code' => $r->code, 'name' => $r->coa_name,
                        'amount' => $cur, 'previous' => $prev, 'change' => self::percentChange($cur, $prev),
                    ];
                })
                ->filter(fn ($a) => $a['amount'] != 0 || $a['previous'] != 0)
                ->values()
                ->all();

            $cur = array_sum(array_column($accounts, 'amount'));
            $prev = array_sum(array_column($accounts, 'previous'));

            $line = [
                'category_id' => $cat->id,
                'category' => $cat->name,
                'amount' => $cur,
                'previous' => $prev,
                'change' => self::percentChange($cur, $prev),
                'accounts' => $accounts,
            ];

            if ($cat->type === CategoryType::Income) {
                $income[] = $line;
            } else {
                $expense[] = $line;
            }
        }

        $totalIncome = $this->monthTotal($income);
        $totalExpense = $this->monthTotal($expense);

        return [
            'year' => $year,
            'month' => $month,
            'income' => $this->withShare($income, $totalIncome['amount']),
            'expense' => $this->withShare($expense, $totalExpense['amount']),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_income' => $this->pair(
                $totalIncome['amount'] - $totalExpense['amount'],
                $totalIncome['previous'] - $totalExpense['previous'],
            ),
        ];
    }

    private function pair(float $amount, float $previous): array
    {
        return ['amount' => $amount, 'previous' => $previous, 'change' => self::percentChange($amount, $previous)];
    }

    private function monthTotal(array $lines): array
    {
        return $this->pair(array_sum(array_column($lines, 'amount')), array_sum(array_column($lines, 'previous')));
    }

    private function withShare(array $lines, float $total): array
    {
        return array_map(
            fn ($l) => $l + ['share' => $total != 0 ? round($l['amount'] / $total * 100, 1) : null],
            $lines,
        );
    }

    /* ---------- Harian: untuk grafik dashboard mode bulanan ---------- */

    public function daily(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1, 0, 0, 0);
        $end = $start->copy()->endOfMonth();
        $days = $start->daysInMonth;

        $rows = $this->base()
            ->whereBetween('t.date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('t.date')
            ->selectRaw("t.date as d,
                SUM(CASE WHEN c.type = 'income' THEN t.credit - t.debit ELSE 0 END) as income,
                SUM(CASE WHEN c.type = 'expense' THEN t.debit - t.credit ELSE 0 END) as expense")
            ->get();

        $income = array_fill(0, $days, 0);
        $expense = array_fill(0, $days, 0);

        foreach ($rows as $r) {
            $i = (int) substr($r->d, 8, 2) - 1;
            $income[$i] = (float) $r->income;
            $expense[$i] = (float) $r->expense;
        }

        return ['labels' => range(1, $days), 'income' => $income, 'expense' => $expense];
    }
}