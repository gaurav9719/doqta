<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JournalTopic;

class JournalTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            'Anxiety',
            'Depression',
            'Stress',
            'Bipolar disorder',
            'Eating disorders',
            'Obsessive-compulsive disorder (OCD)',
            'Post-traumatic stress disorder (PTSD)',
            'Attention deficit hyperactivity disorder (ADHD)',
            'Panic attacks',
            'Social anxiety',
            'Generalized anxiety disorder (GAD)',
            'Phobias',
            'Self-esteem',
            'Body image',
            'Addiction recovery',
            'Trauma',
            'Self-harm',
            'Insomnia',
            'Relationship issues',
            'Grief and loss',
            'Work-related stress',
            'Parenting stress',
            'Chronic illness management',
            'Coping strategies',
            'Mindfulness and meditation'
        ];

        foreach ($topics as $topic) {
            JournalTopic::create(['name' => $topic]);
        }

    }
}
