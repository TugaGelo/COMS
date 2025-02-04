<?php

namespace Database\Seeders;

use App\Models\ConcourseRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConcourseRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConcourseRate::insert([
            [
                'name' => 'City Rate',
                'price' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Provincial Rate',
                'price' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
