<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class FeelingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feelingsData = [
            // ['feeling' => '😡', 'name' => 'Very Upset'],
            // ['feeling' => '😔', 'name' => 'Upset'],
            // ['feeling' => '😐', 'name' => 'Emotion'],
            // ['feeling' => '😊', 'name' => 'Happy'],
            // ['feeling' => '😄', 'name' => 'Very Happy'],

            ['feeling' => 'feelings/very_upset.png', 'selected'=>'feelings/very_upset_selected.png','name' => 'Very Upset'],
            ['feeling' => 'feelings/upset.png', 'selected'=>'feelings/upset_selected.png','name' => 'Upset'],
            ['feeling' => 'feelings/neutral.png','selected'=>'feelings/neutral_selected.png', 'name' => 'Neutral'],
            ['feeling' => 'feelings/happy.png', 'selected'=>'feelings/happy_selected.png','name' => 'Happy'],
            ['feeling' => 'feelings/very_happy.png','selected'=>'feelings/very_happy_selected.png', 'name' => 'Very Happy'],
            
            // Add more emotions as needed
        ];
    
        foreach ($feelingsData as $data) {
            // Insert feeling into the database
            $feelingId = DB::table('feelings')->insertGetId([
                'feeling' => $data['feeling'],
                'selected' => $data['selected'],
                'name' => $data['name'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Associate types with feelings
            $types = [];
            switch ($data['name']) {
                case 'Very Upset':
                case 'Upset':
                    $types = ['Angry', 'Furious', 'Irritated', 'Frustrated', 'Enraged', 'Annoyed', 'Resentful', 'Hostile', 'Aggravated', 'Outraged', 'Displeased', 'Sad', 'Depressed', 'Miserable', 'Hopeless'];
                    break;
                case 'Neutral':
                    $types = ['Confused', 'Ambivalent', 'Mixed', 'Uncertain', 'Indecisive', 'Overwhelmed', 'Anxious', 'Stressed', 'Tense', 'Nervous', 'Worried', 'Restless', 'Uneasy', 'Fidgety', 'Panicked'];
                    break;
                case 'Happy':
                    $types = ['Joyful', 'Content', 'Satisfied', 'Pleased', 'Optimistic', 'Grateful', 'Cheerful', 'Euphoric', 'Excited', 'Ecstatic', 'Delighted', 'Blissful', 'Radiant', 'Enthusiastic', 'Elated'];
                    break;
                case 'Very Happy':
                    $types = ['Exhilarated', 'Overjoyed', 'Thrilled', 'Ecstatic', 'Blessed', 'Lucky', 'Grateful', 'Jubilant', 'Elated', 'Euphoric', 'Content', 'Satisfied', 'Blissful', 'Radiant', 'Enthusiastic'];
                    break;
                // Add more cases for additional emotions
            }
    
            // Insert types and associate them with the feeling
            foreach ($types as $typeName) {
                // Check if the type already exists
                $typeId = DB::table('feelings')->where('id', $feelingId)->value('id');
             
                if (isset($typeId) && !empty($typeId)) {
                   
                    DB::table('feeling_types')->insert([
                        'feeling_id' => $feelingId,
                        'name' => $typeName,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                // Associate the type with the feeling
            }
        }
    }
    
}
