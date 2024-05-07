<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JournalTopic;
use Illuminate\Support\Facades\DB;


class JournalTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $topics = [
        //     'Anxiety',
        //     'Depression',
        //     'Stress',
        //     'Bipolar disorder',
        //     'Eating disorders',
        //     'Obsessive-compulsive disorder (OCD)',
        //     'Post-traumatic stress disorder (PTSD)',
        //     'Attention deficit hyperactivity disorder (ADHD)',
        //     'Panic attacks',
        //     'Social anxiety',
        //     'Generalized anxiety disorder (GAD)',
        //     'Phobias',
        //     'Self-esteem',
        //     'Body image',
        //     'Addiction recovery',
        //     'Trauma',
        //     'Self-harm',
        //     'Insomnia',
        //     'Relationship issues',
        //     'Grief and loss',
        //     'Work-related stress',
        //     'Parenting stress',
        //     'Chronic illness management',
        //     'Coping strategies',
        //     'Mindfulness and meditation'
        // ];
        $topics =[

            ['name' => 'Anxiety', 'icon' => 'interest/icon-anxiety.png', 'type' => 1],
            ['name' => 'Depression', 'icon' => 'interest/icon-depression.png', 'type' => 1],
            ['name' => 'Post-traumatic stress disorder', 'icon' => 'interest/icon-post-traumatic.png', 'type' => 1],
            ['name' => 'Hypertension', 'icon' => 'interest/icon-hypertension.png', 'type' => 1],
            ['name' => 'Diabetes', 'icon' => 'interest/icon-diabetes.png', 'type' => 1],
            ['name' => 'Obesity', 'icon' => 'interest/icon-obesity.png', 'type' => 1],
            ['name' => 'Sexual health', 'icon' => 'interest/sexual_health.png', 'type' => 1],
            ['name' => 'Maternal health', 'icon' => 'interest/maternal_health.png', 'type' => 1],
            ['name' => 'Cancer', 'icon' => 'interest/cancer.png', 'type' => 1],
            ['name' => 'Sickle cell disease', 'icon' => 'interest/sickle_cell_disease.png', 'type' => 1],
            ['name' => 'Other', 'icon' => 'interest/other.png', 'type' => 2],

        ];

        foreach ($topics as $topic) {
            DB::table('journal_topics')->insert([
                'name' => $topic['name'],
                'icon' => $topic['icon'],
                'type' => $topic['type'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
