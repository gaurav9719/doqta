<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $documentTypes = [
            ['name' => 'Passport'],
            ['name' => 'Driver\'s License'],
            ['name' => 'National ID Card'],
            ['name' => 'Birth Certificate'],
            ['name' => 'Social Security Card'],
            ['name' => 'Voter ID Card'],
            ['name' => 'Residence Permit'],
            ['name' => 'Tax ID Card'],
            ['name' => 'Military ID'],
            ['name' => 'Health Insurance Card'],
            ['name' => 'Student ID Card'],
            ['name' => 'Employee ID Card'],
            ['name' => 'Marriage Certificate'],
            ['name' => 'Divorce Decree'],
            ['name' => 'Bank Statement'],
            ['name' => 'Utility Bill'],
            ['name' => 'Vehicle Registration'],
            ['name' => 'Insurance Policy'],
            ['name' => 'Business License'],
            ['name' => 'Professional License'],
            ['name' => 'Certificate of Incorporation'],
            // Add more document types as needed
        ];

        DB::table('document_types')->insert($documentTypes);

        
    }
}
