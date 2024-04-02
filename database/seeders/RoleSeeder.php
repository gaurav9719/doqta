<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        DB::table('roles')->insert([
            ['name' => 'Admin'],
            ['name' => 'Dater'],
            ['name' => 'Recruiter'],
            ['name' => 'Roaster AI User'],
            // Add more roles as needed
        ]);
    }
}
