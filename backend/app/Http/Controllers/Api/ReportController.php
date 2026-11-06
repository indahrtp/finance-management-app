<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;
use App\Exports\ProfitLossExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function profitLoss(Request $request, ProfitLossService $service)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = $data['year'] ?? now()->year;

        return response()->json($service->generate($year));
    }

    public function exportProfitLoss(Request $request)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = $data['year'] ?? now()->year;

        return Excel::download(new ProfitLossExport($year), "laporan-profit-loss-{$year}.xlsx");
    }
}