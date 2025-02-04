<?php

namespace Database\Seeders;


use App\Models\Application;
use App\Models\Concourse;
use App\Models\ConcourseRate;
use App\Models\User;
use App\Models\Space;
use App\Models\Tenant;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ConcourseRateSeeder::class);
        $this->call(ConcourseSeeder::class);
        $this->call(SpaceSeeder::class);
        $this->call(ApplicationSeeder::class);
        $this->call(RequirementSeeder::class);
        $this->call(PaymentSeeder::class);
    }
}
