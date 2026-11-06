<?php

namespace App\Exports;

use App\Services\ProfitLossService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private array $report;

    // Nomor baris (1-based) untuk pewarnaan
    private array $incomeRows = [];
    private array $expenseRows = [];
    private int $totalIncomeRow = 0;
    private int $totalExpenseRow = 0;
    private int $netRow = 0;

    public function __construct(private int $year)
    {
        $this->report = app(ProfitLossService::class)->generate($year);
    }

    public function array(): array
    {
        $r = $this->report;

        $rows = [];
        $rows[] = ["Laporan Profit/Loss {$this->year}"];
        $rows[] = array_merge(['Kategori'], self::MONTHS, ['Total']);

        foreach ($r['income'] as $line) {
            $rows[] = $this->line($line['category'], $line);
            $this->incomeRows[] = count($rows);
        }

        $rows[] = $this->line('Total Income', $r['total_income']);
        $this->totalIncomeRow = count($rows);

        foreach ($r['expense'] as $line) {
            $rows[] = $this->line($line['category'], $line);
            $this->expenseRows[] = count($rows);
        }

        $rows[] = $this->line('Total Expense', $r['total_expense']);
        $this->totalExpenseRow = count($rows);

        $rows[] = $this->line('Net Income', $r['net_income']);
        $this->netRow = count($rows);

        return $rows;
    }

    private function line(string $label, array $data): array
    {
        return array_merge([$label], $data['months'], [$data['total']]);
    }

    public function styles(Worksheet $sheet): array
    {
        // styles() dipanggil setelah array(), jadi nomor baris sudah terisi
        $lastCol = 'N'; // Kategori + 12 bulan + Total = 14 kolom (A sampai N)

        $sheet->mergeCells("A1:{$lastCol}1");

        // Format angka ribuan untuk seluruh data
        $sheet->getStyle("B3:{$lastCol}{$this->netRow}")
            ->getNumberFormat()->setFormatCode('#,##0');

        $fill = fn (string $range, string $color) => $sheet->getStyle($range)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);

        // Header kuning seperti Gambar 4
        $fill("A2:{$lastCol}2", 'FFFF00');

        foreach ($this->incomeRows as $row) {
            $fill("A{$row}:{$lastCol}{$row}", 'C6E0B4');
        }
        $fill("A{$this->totalIncomeRow}:{$lastCol}{$this->totalIncomeRow}", 'A9D08E');

        foreach ($this->expenseRows as $row) {
            $fill("A{$row}:{$lastCol}{$row}", 'F8CBAD');
        }
        $fill("A{$this->totalExpenseRow}:{$lastCol}{$this->totalExpenseRow}", 'F4B084');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            $this->totalIncomeRow => ['font' => ['bold' => true]],
            $this->totalExpenseRow => ['font' => ['bold' => true]],
            $this->netRow => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return "P&L {$this->year}";
    }
}