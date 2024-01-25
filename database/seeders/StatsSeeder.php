<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $questions = [
            'Called my parents in the past month',
            'Dad jokes made this week',
            'Used a fake Euro accent in the past week',
            'Returned shopping carts in the past month',
            'Dog belly scratches given this week',
            'Cooked a homemade meal this week',
            'Ordered food delivery this week',
            'Went to a workout class in the past two weeks',
            'Gone hiking in the past month',
            'Books read in the past month',
            'Flossed my teeth in the past two weeks',
            'Hours spent on TikTok in my average day',
        ];

        foreach ($questions as $index => $question) {
            DB::table('stats')->insert([
                'question' => $question,
                'min_value' =>  0,
                'max_value' => 10,
                'is_active' => true,
            ]);
        } 
    }
}
