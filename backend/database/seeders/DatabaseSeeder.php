<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            CoaSeeder::class,
            WalletSeeder::class,
            TransactionSeeder::class,
            BudgetSeeder::class,
        ]);
    }
}