<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ProfitLossService $service)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . now()->year],
            'month' => ['nullable', 'date_format:Y-m', function ($attribute, $value, $fail) {
                if ($value > now()->format('Y-m')) {
                    $fail('Periode tidak boleh di masa depan.');
                }
            }],
        ]);

        $pct = [ProfitLossService::class, 'percentChange'];

        if (! empty($data['month'])) {
            [$year, $month] = array_map('intval', explode('-', $data['month']));
            $r = $service->generateMonthly($year, $month);

            $summary = [
                'total_income' => $r['total_income']['amount'],
                'total_expense' => $r['total_expense']['amount'],
                'balance' => $r['net_income']['amount'],
            ];
            $previous = [
                'total_income' => $r['total_income']['previous'],
                'total_expense' => $r['total_expense']['previous'],
                'balance' => $r['net_income']['previous'],
            ];
            $chart = ['granularity' => 'day'] + $service->daily($year, $month);
            $breakdown = collect($r['expense'])->map(fn ($l) => ['category' => $l['category'], 'total' => $l['amount']]);
            $mode = 'month';
            $label = 'dibanding bulan lalu';
        } else {
            $year = $data['year'] ?? now()->year;
            $month = null;
            $r = $service->generate($year);
            $p = $service->generate($year - 1);

            $summary = [
                'total_income' => $r['total_income']['total'],
                'total_expense' => $r['total_expense']['total'],
                'balance' => $r['net_income']['total'],
            ];
            $previous = [
                'total_income' => $p['total_income']['total'],
                'total_expense' => $p['total_expense']['total'],
                'balance' => $p['net_income']['total'],
            ];
            $chart = [
                'granularity' => 'month',
                'labels' => $r['months'],
                'income' => $r['total_income']['months'],
                'expense' => $r['total_expense']['months'],
            ];
            $breakdown = collect($r['expense'])->map(fn ($l) => ['category' => $l['category'], 'total' => $l['total']]);
            $mode = 'year';
            $label = 'dibanding tahun lalu';
        }

        $recent = Transaction::posted()->with('coa.category')
            ->orderByDesc('date')->orderByDesc('id')->limit(5)->get();

        return response()->json([
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
            'comparison' => [
                'label' => $label,
                'income' => $pct($summary['total_income'], $previous['total_income']),
                'expense' => $pct($summary['total_expense'], $previous['total_expense']),
                'balance' => $pct($summary['balance'], $previous['balance']),
            ],
            'chart' => $chart,
            'expense_breakdown' => $breakdown->values(),
            'cash_balance' => Wallet::withBalance()->get()->sum('balance'),
            'recent_transactions' => $recent,
        ]);
    }
}