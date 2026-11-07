<?php

namespace Database\Seeders;

use App\Models\Coa;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    // Pengali variasi pengeluaran per bulan (Jan sampai Des)
    private const FACTORS = [1.00, 0.95, 1.10, 1.05, 0.90, 1.15, 1.00, 1.08, 0.97, 1.12, 1.03, 1.00];

    // Profit trading per bulan (Jan sampai Des)
    private const TRADING = [
        5500000, 6000000, 3500000, 4200000, 2800000, 5100000,
        3900000, 6300000, 4700000, 3200000, 4000000, 4500000,
    ];

    public function run(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $coa = Coa::pluck('id', 'code');
        $wallets = Wallet::pluck('id', 'name');

        $today = Carbon::today();
        $year = $today->year;

        // Tanggal yang belum tiba dilewati, jadi tidak ada transaksi di masa depan
        $add = function (
            int $month, int $day, string $code, string $wallet, string $description,
            int $amount, ?string $reference = null, ?string $notes = null,
        ) use ($user, $coa, $wallets, $year, $today) {
            $date = Carbon::create($year, $month, $day);

            if ($date->gt($today)) {
                return;
            }

            $isIncome = str_starts_with($code, '4'); // 4xx income = credit, 6xx expense = debit

            Transaction::create([
                'date' => $date->toDateString(),
                'coa_id' => $coa[$code],
                'wallet_id' => $wallets[$wallet],
                'user_id' => $user->id,
                'reference_no' => $reference,
                'description' => $description,
                'notes' => $notes,
                'debit' => $isIncome ? 0 : $amount,
                'credit' => $isIncome ? $amount : 0,
                'status' => 'posted',
            ]);
        };

        for ($m = 1; $m <= $today->month; $m++) {
            $f = self::FACTORS[$m - 1];
            $ym = sprintf('%d%02d', $year, $m);
            $semester = in_array($m, [1, 7], true); // awal semester: ada uang pangkal

            // Pemasukan
            $add($m, 1, '401', 'Rekening Bank', 'Gaji di Perusahaan A', 5000000, "SLIP-{$ym}-01");
            $add($m, 2, '402', 'Rekening Bank', 'Gaji Ketum', 7000000, "SLIP-{$ym}-02");
            $add($m, 5, '403', 'E-Wallet', 'Profit trading', self::TRADING[$m - 1], "TRD-{$ym}");

            // Pengeluaran bulanan
            $add($m, 3, '605', 'Rekening Bank', 'Belanja bulanan', $this->round(1200000 * $f),
                null, 'Beras, minyak, dan kebutuhan dapur');
            $add($m, 8, '601', 'Rekening Bank', 'Biaya sekolah', $semester ? 4500000 : 1500000,
                "INV-SPP-{$ym}", $semester ? 'Uang semester dan SPP' : 'SPP bulanan');

            // Bensin: mingguan
            foreach ([4 => 200000, 11 => 175000, 18 => 225000, 25 => 150000] as $day => $base) {
                $add($m, $day, '602', 'Rekening Bank', 'Bensin', $this->round($base * $f));
            }

            // Parkir: dua kali sebulan
            foreach ([10 => 50000, 24 => 35000] as $day => $base) {
                $add($m, $day, '603', 'E-Wallet', 'Parkir', $this->round($base * $f));
            }

            // Makan siang: mingguan
            foreach ([7 => 60000, 14 => 70000, 21 => 55000, 28 => 75000] as $day => $base) {
                $add($m, $day, '604', 'Kas Tunai', 'Makan siang', $this->round($base * $f));
            }
        }

        // Contoh status lain: keduanya TIDAK ikut terhitung di laporan
        Transaction::create([
            'date' => $today->toDateString(),
            'coa_id' => $coa['604'],
            'wallet_id' => $wallets['Kas Tunai'],
            'user_id' => $user->id,
            'description' => 'Makan malam (draft)',
            'debit' => 90000,
            'credit' => 0,
            'status' => 'draft',
        ]);

        Transaction::create([
            'date' => $today->copy()->subDays(10)->max(Carbon::create($year, 1, 1))->toDateString(),
            'coa_id' => $coa['602'],
            'wallet_id' => $wallets['Kas Tunai'],
            'user_id' => $user->id,
            'description' => 'Salah input bensin (dibatalkan)',
            'debit' => 500000,
            'credit' => 0,
            'status' => 'void',
        ]);
    }

    // Bulatkan ke kelipatan 5.000 supaya nominal terlihat wajar
    private function round(float $value): int
    {
        return (int) (round($value / 5000) * 5000);
    }
}