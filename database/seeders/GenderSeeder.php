<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $genders = [
            ['name' => 'Male'],
            ['name' => 'Female'],
            ['name' => 'Non-binary'],
            ['name' => 'Prefer not to specify'],

            // Add more genders as needed
        ];
        foreach ($genders as $gender) {
            DB::table('genders')->insert([
                'name' => $gender['name'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


    }
}
