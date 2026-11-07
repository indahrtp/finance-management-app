<?php

namespace Database\Seeders;

use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Kas Tunai', 'cash', 5000000],
            ['Rekening Bank', 'bank', 10000000],
            ['E-Wallet', 'ewallet', 500000],
        ] as [$name, $type, $opening]) {
            Wallet::updateOrCreate(
                ['name' => $name],
                ['type' => $type, 'opening_balance' => $opening],
            );
        }
    }
}