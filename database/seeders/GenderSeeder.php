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
            ['name' => 'Transgender Male'],
            ['name' => 'Transgender Female'],
            ['name' => 'Gender non-conforming'],
            ['name' => 'Prefer not to disclose'],
          

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
