<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ProfitLossService $service)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = $data['year'] ?? now()->year;
        $report = $service->generate($year);

        $expenseBreakdown = collect($report['expense'])
            ->map(fn ($line) => [
                'category' => $line['category'],
                'total' => $line['total'],
            ])
            ->values();

        $recent = Transaction::with('coa.category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return response()->json([
            'year' => $year,
            'summary' => [
                'total_income' => $report['total_income']['total'],
                'total_expense' => $report['total_expense']['total'],
                'balance' => $report['net_income']['total'],
            ],
            'monthly' => [
                'months' => $report['months'],
                'income' => $report['total_income']['months'],
                'expense' => $report['total_expense']['months'],
                'net' => $report['net_income']['months'],
            ],
            'expense_breakdown' => $expenseBreakdown,
            'recent_transactions' => $recent,
        ]);
    }
}