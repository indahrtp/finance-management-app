<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coa;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        foreach ([
            ['401', 'Gaji Karyawan', 'Salary'],
            ['402', 'Gaji Ketua MPR', 'Salary'],
            ['403', 'Profit Trading', 'Other Income'],
            ['601', 'Biaya Sekolah', 'Family Expense'],
            ['602', 'Bensin', 'Transport Expense'],
            ['603', 'Parkir', 'Transport Expense'],
            ['604', 'Makan Siang', 'Meal Expense'],
            ['605', 'Makanan Pokok Bulanan', 'Meal Expense'],
        ] as [$code, $name, $category]) {
            Coa::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'category_id' => $categories[$category]],
            );
        }
    }
}