<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Concourse;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Application::factory()->count(10)->create();
    }
}
