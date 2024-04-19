<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interests = [
            ['name' => 'Hypertension', 'icon' => 'interest/icon-hypertension.png'],
            ['name' => 'Obesity', 'icon' => 'interest/icon-obesity.png'],
            ['name' => 'Diabetes', 'icon' => 'interest/icon-diabetes.png'],
            ['name' => 'Depression', 'icon' => 'interest/icon-depression.png'],
            ['name' => 'Anxiety', 'icon' => 'interest/icon-anxiety.png'],
            ['name' => 'PTSD', 'icon' => 'interest/icon-ptsd.png'],
        ];

        foreach ($interests as $interest) {
            DB::table('interests')->insert([
                'name' => $interest['name'],
                'icon' => $interest['icon'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
