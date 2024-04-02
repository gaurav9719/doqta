<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParticipantCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            ['name' => 'Patient', 'reason' => 'I am here for my own health'],
            ['name' => 'Caretaker', 'reason' => 'I am here for another person’s health'],
            ['name' => 'Health provider', 'reason' => 'I am a doctor'],
        ];

        foreach ($categories as $category) {
            DB::table('participant_categories')->insert([
                'name' => $category['name'],
                'reason' => $category['reason'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
