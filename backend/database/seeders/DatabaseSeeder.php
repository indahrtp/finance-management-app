<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coa;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $cats = [];
        foreach ([
            ['Salary', 'income'],
            ['Other Income', 'income'],
            ['Family Expense', 'expense'],
            ['Transport Expense', 'expense'],
            ['Meal Expense', 'expense'],
        ] as [$name, $type]) {
            $cats[$name] = Category::create(['name' => $name, 'type' => $type]);
        }

        $coa = [];
        foreach ([
            ['401', 'Gaji Karyawan', 'Salary'],
            ['402', 'Gaji Ketua MPR', 'Salary'],
            ['403', 'Profit Trading', 'Other Income'],
            ['601', 'Biaya Sekolah', 'Family Expense'],
            ['602', 'Bensin', 'Transport Expense'],
            ['603', 'Parkir', 'Transport Expense'],
            ['604', 'Makan Siang', 'Meal Expense'],
            ['605', 'Makanan Pokok Bulanan', 'Meal Expense'],
        ] as [$code, $name, $cat]) {
            $coa[$code] = Coa::create([
                'code' => $code, 'name' => $name, 'category_id' => $cats[$cat]->id,
            ]);
        }

        foreach ([
            ['2022-01-01', '401', 'Gaji Di Persuhaan A', 0, 5000000],
            ['2022-01-02', '402', 'Gaji Ketum', 0, 7000000],
            ['2022-01-10', '602', 'Bensin Anak', 25000, 0],
        ] as [$date, $code, $desc, $debit, $credit]) {
            Transaction::create([
                'date' => $date, 'coa_id' => $coa[$code]->id, 'user_id' => $user->id,
                'description' => $desc, 'debit' => $debit, 'credit' => $credit,
            ]);
        }
    }
}