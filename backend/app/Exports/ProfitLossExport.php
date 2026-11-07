<?php

namespace App\Exports;

use App\Services\ProfitLossService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const LAST_COL = 'N'; // Kategori + 12 bulan + Total
    private const HEADER_ROW = 5;
    // positif | negatif merah | nol tampil "-"
    private const NUMBER_FORMAT = '#,##0;[Red]-#,##0;"-"';

    private array $rows = [];
    private array $layout = []; // [nomor baris => jenis baris]

    public function __construct(private int $year)
    {
        $this->build(app(ProfitLossService::class)->generate($year));
    }

    private function add(array $row, string $type): void
    {
        $this->rows[] = $row;
        $this->layout[count($this->rows)] = $type;
    }

    private function line(string $label, array $data): array
    {
        return array_merge([$label], $data['months'], [$data['total']]);
    }

    private function build(array $r): void
    {
        $printed = Carbon::now()->locale('id')->translatedFormat('d F Y, H:i');

        $this->add(['Laporan Profit/Loss'], 'title');
        $this->add(["Periode 1 Januari - 31 Desember {$this->year}  |  Dalam Rupiah (IDR)"], 'subtitle');
        $this->add(["Dicetak pada {$printed}"], 'meta');
        $this->add([null], 'blank');
        $this->add(array_merge(['Kategori'], self::MONTHS, ['Total']), 'header');

        $this->add(['PENDAPATAN'], 'section');
        foreach ($r['income'] as $l) {
            $this->add($this->line($l['category'], $l), 'line');
        }
        $this->add($this->line('Total Income', $r['total_income']), 'total_income');

        $this->add(['PENGELUARAN'], 'section');
        foreach ($r['expense'] as $l) {
            $this->add($this->line($l['category'], $l), 'line');
        }
        $this->add($this->line('Total Expense', $r['total_expense']), 'total_expense');

        $this->add($this->line('Net Income', $r['net_income']), 'net');

        $this->add([null], 'blank');
        $this->add(['Keterangan: Income = credit - debit, Expense = debit - credit. Net Income = Total Income - Total Expense.'], 'note');
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 28, 'N' => 17];
        foreach (range('B', 'M') as $col) {
            $widths[$col] = 13;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        $last = self::LAST_COL;
        $lastRow = count($this->rows);

        $all = $sheet->getStyle("A1:{$last}{$lastRow}");
        $all->getFont()->setName('Calibri')->setSize(10);
        $all->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $numberStyle = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'indent' => 1],
            'numberFormat' => ['formatCode' => self::NUMBER_FORMAT],
        ];
        $solid = fn (string $rgb) => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
        $border = fn (string $style, string $rgb) => ['borderStyle' => $style, 'color' => ['rgb' => $rgb]];
        $indent = fn (int $n) => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => $n];

        foreach ($this->layout as $row => $type) {
            $range = "A{$row}:{$last}{$row}";
            $label = "A{$row}";
            $numbers = "B{$row}:{$last}{$row}";

            switch ($type) {
                case 'title':
                    $sheet->getStyle($label)->applyFromArray(['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0F172A']]]);
                    $sheet->getRowDimension($row)->setRowHeight(28);
                    break;

                case 'subtitle':
                    $sheet->getStyle($label)->applyFromArray(['font' => ['size' => 10, 'color' => ['rgb' => '475569']]]);
                    break;

                case 'meta':
                case 'note':
                    $sheet->getStyle($label)->applyFromArray(['font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']]]);
                    break;

                case 'header':
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => $solid('1E293B'),
                    ]);
                    $sheet->getStyle($numbers)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
                    $sheet->getStyle($label)->applyFromArray(['alignment' => $indent(1)]);
                    $sheet->getRowDimension($row)->setRowHeight(24);
                    break;

                case 'section':
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
                        'borders' => ['bottom' => $border(Border::BORDER_THIN, 'CBD5E1')],
                    ]);
                    $sheet->getStyle($label)->applyFromArray(['alignment' => $indent(1)]);
                    $sheet->getRowDimension($row)->setRowHeight(26);
                    break;

                case 'line':
                    $sheet->getStyle($range)->applyFromArray(['borders' => ['bottom' => $border(Border::BORDER_HAIR, 'CBD5E1')]]);
                    $sheet->getStyle($numbers)->applyFromArray($numberStyle);
                    $sheet->getStyle($label)->applyFromArray(['alignment' => $indent(2)]);
                    $sheet->getRowDimension($row)->setRowHeight(19);
                    break;

                case 'total_income':
                case 'total_expense':
                    $income = $type === 'total_income';
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $income ? '047857' : 'C2410C']],
                        'fill' => $solid($income ? 'ECFDF5' : 'FFF7ED'),
                        'borders' => ['top' => $border(Border::BORDER_THIN, 'CBD5E1')],
                    ]);
                    $sheet->getStyle($numbers)->applyFromArray($numberStyle);
                    $sheet->getStyle($label)->applyFromArray(['alignment' => $indent(1)]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                    break;

                case 'net':
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                        'fill' => $solid('F1F5F9'),
                        'borders' => [
                            'top' => $border(Border::BORDER_THIN, '94A3B8'),
                            'bottom' => $border(Border::BORDER_DOUBLE, '1E293B'),
                        ],
                    ]);
                    $sheet->getStyle($numbers)->applyFromArray($numberStyle);
                    $sheet->getStyle($label)->applyFromArray(['alignment' => $indent(1)]);
                    $sheet->getRowDimension($row)->setRowHeight(26);
                    break;
            }
        }

        // Tampilan layar
        $sheet->setShowGridlines(false);
        $sheet->freezePane('B' . (self::HEADER_ROW + 1));
        $sheet->getTabColor()->setRGB('465FFF');

        // Pengaturan cetak
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setPrintArea("A1:{$last}{$lastRow}");
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);
        $sheet->getHeaderFooter()->setOddFooter('&LFinanceApp&RHalaman &P dari &N');

        return [];
    }

    public function title(): string
    {
        return "P&L {$this->year}";
    }
}