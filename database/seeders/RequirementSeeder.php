<?php

namespace Database\Seeders;

use App\Models\Requirement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requirements = [
            ['name' => 'Letter of Intent'],
            ['name' => 'DTI Certificate/SEC Registration'],
            ['name' => 'Business Permit'],
            ['name' => 'Barangay Clearance'],
            ['name' => 'Sanitary Permit (for food stall)'],
            ['name' => 'Health Certificate of Personnel'],
            ['name' => 'Proof of Billing (in the name of application)'],
            ['name' => 'Photocopy of Government Issued ID'],
            ['name' => 'Organizational Chart with Photo'],
            ['name' => 'List of Menu (for food stall)'],
            ['name' => 'List of Office Supplies fro sale (non-food)'],
            ['name' => 'Services Offered (non-food)'],
            ['name' => 'Business Application Fee (PHP 150.00)'],
        ];

        foreach ($requirements as $requirement) {
            Requirement::create($requirement);
        }
    }
}
