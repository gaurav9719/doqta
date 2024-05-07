<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PronounsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $pronouns = [
            ['subjective' => 'He', 'objective' => 'him', 'possessive' => 'his'],
            ['subjective' => 'She', 'objective' => 'her', 'possessive' => 'hers'],
            ['subjective' => 'They', 'objective' => 'them', 'possessive' => 'theirs'],
            ['subjective' => 'Prefer not to disclose', 'objective' =>null , 'possessive' =>null]
            // Add more pronouns as needed
        ];

        foreach ($pronouns as $pronoun) {
            DB::table('pronouns')->insert([
                'subjective' => $pronoun['subjective'],
                'objective' => $pronoun['objective'],
                'possessive' => $pronoun['possessive'],
                // 'possessive_plural' => $pronoun['possessive_plural'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
