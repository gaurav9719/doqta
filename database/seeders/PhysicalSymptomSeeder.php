<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhysicalSymptomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $symptoms = [
            'Fast heartbeat',
            'Trembling',
            'Sweating',
            'Nausea',
            'Shortness of breath',
            'Dizziness',
            'Chest pain',
            'Headache',
            'Muscle tension',
            'Fatigue',
            'Fainting',
            'Hot flashes',
            'Dry mouth',
            'Stomach discomfort',
            'Restlessness',
            'Difficulty swallowing'
        ];

        foreach ($symptoms as $symptom) {
            DB::table('physical_symptoms')->insert([
                'symptom' => $symptom,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
