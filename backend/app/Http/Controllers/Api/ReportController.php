<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;
use App\Exports\ProfitLossExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function profitLoss(Request $request, ProfitLossService $service)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . now()->year],
        ]);

        $year = $data['year'] ?? now()->year;

        return response()->json($service->generate($year));
    }

    public function exportProfitLoss(Request $request)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . now()->year],
        ]);

        $year = $data['year'] ?? now()->year;

        return Excel::download(new ProfitLossExport($year), "laporan-profit-loss-{$year}.xlsx");
    }

    public function profitLossMonthly(Request $request, ProfitLossService $service)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m', function ($attribute, $value, $fail) {
                if ($value > now()->format('Y-m')) {
                    $fail('Periode tidak boleh di masa depan.');
                }
            }],
        ]);
        [$year, $month] = array_map('intval', explode('-', $data['month']));

        return response()->json($service->generateMonthly($year, $month));
    }

    public function periodRange()
    {
        $dates = array_filter([
            DB::table('transactions')->min('date'),
            DB::table('budgets')->min('period'),
        ]);

        return response()->json([
            'min' => $dates ? substr(min($dates), 0, 7) : now()->format('Y-m'),
        ]);
    }
}