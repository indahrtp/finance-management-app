<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    // Anggaran vs realisasi
    public function index(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m', function ($attribute, $value, $fail) {
                if ($value > now()->format('Y-m')) {
                    $fail('Periode tidak boleh di masa depan.');
                }
            }],
        ]);

        // tanggal = 1
        $start = Carbon::createFromFormat('!Y-m', $data['month'] ?? now()->format('Y-m'));
        $end = $start->copy()->endOfMonth();

        $budgets = Budget::where('period', $start->toDateString())
            ->get()
            ->keyBy('category_id');

        // Realisasi = total pengeluaran (debit - credit) per kategori pada bulan itu
        $actuals = DB::table('transactions as t')
            ->join('coa as a', 'a.id', '=', 't.coa_id')
            ->where('t.status', 'posted')
            ->whereBetween('t.date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('a.category_id')
            ->selectRaw('a.category_id, SUM(t.debit - t.credit) as actual')
            ->pluck('actual', 'category_id');

        $rows = Category::where('type', CategoryType::Expense->value)
            ->orderBy('name')
            ->get()
            ->map(function ($cat) use ($budgets, $actuals) {
                $budget = $budgets->get($cat->id);
                $amount = $budget ? (float) $budget->amount : null;
                $actual = (float) $actuals->get($cat->id, 0);
                $percent = $amount ? round($actual / $amount * 100, 1) : null;

                $status = match (true) {
                    $percent === null => 'none',
                    $percent > 100 => 'over',
                    $percent >= 80 => 'warning',
                    default => 'safe',
                };

                return [
                    'category_id' => $cat->id,
                    'category' => $cat->name,
                    'budget_id' => $budget?->id,
                    'budget' => $amount,
                    'actual' => $actual,
                    'remaining' => $amount !== null ? $amount - $actual : null,
                    'percent' => $percent,
                    'status' => $status,
                ];
            });

        $budgeted = $rows->whereNotNull('budget');

        return response()->json([
            'month' => $start->format('Y-m'),
            'summary' => [
                'total_budget' => $budgeted->sum('budget'),
                'total_actual' => $budgeted->sum('actual'),
                // pengeluaran di kategori yang belum punya anggaran
                'unbudgeted_actual' => $rows->whereNull('budget')->sum('actual'),
            ],
            'items' => $rows->values(),
        ]);
    }

    // Buat atau ubah anggaran (upsert)
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('type', CategoryType::Expense->value),
            ],
            'month' => ['required', 'date_format:Y-m', function ($attribute, $value, $fail) {
                if ($value > now()->format('Y-m')) {
                    $fail('Periode tidak boleh di masa depan.');
                }
            }],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
        ], [
            'category_id.exists' => 'Anggaran hanya bisa dibuat untuk kategori expense.',
        ]);

        $period = Carbon::createFromFormat('!Y-m', $data['month'])->toDateString();

        $budget = Budget::updateOrCreate(
            ['category_id' => $data['category_id'], 'period' => $period],
            ['amount' => $data['amount']],
        );

        return response()->json($budget, $budget->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return response()->noContent();
    }
}