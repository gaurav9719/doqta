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
            // Health and Wellness (topic_id: 1)
            ['symptom' => 'Fatigue', 'topic_id' => 1],
            ['symptom' => 'Stress', 'topic_id' => 1],
            ['symptom' => 'Lack of energy', 'topic_id' => 1],
            ['symptom' => 'Poor sleep quality', 'topic_id' => 1],
            ['symptom' => 'Digestive issues', 'topic_id' => 1],
            ['symptom' => 'Headaches', 'topic_id' => 1],
            ['symptom' => 'Muscle tension', 'topic_id' => 1],
            ['symptom' => 'Anxiety', 'topic_id' => 1],
            ['symptom' => 'Depression', 'topic_id' => 1],
            ['symptom' => 'Weight fluctuations', 'topic_id' => 1],
            ['symptom' => 'Skin problems', 'topic_id' => 1],
            ['symptom' => 'Joint pain', 'topic_id' => 1],
            ['symptom' => 'Frequent colds', 'topic_id' => 1],
            ['symptom' => 'Back pain', 'topic_id' => 1],
            ['symptom' => 'High blood pressure', 'topic_id' => 1],
            ['symptom' => 'High cholesterol', 'topic_id' => 1],
            ['symptom' => 'Low libido', 'topic_id' => 1],
        
            // Anxiety (topic_id: 2)
            ['symptom' => 'Restlessness', 'topic_id' => 2],
            ['symptom' => 'Fatigue', 'topic_id' => 2],
            ['symptom' => 'Difficulty concentrating', 'topic_id' => 2],
            ['symptom' => 'Irritability', 'topic_id' => 2],
            ['symptom' => 'Muscle tension', 'topic_id' => 2],
            ['symptom' => 'Sleep disturbances', 'topic_id' => 2],
            ['symptom' => 'Increased heart rate', 'topic_id' => 2],
            ['symptom' => 'Sweating', 'topic_id' => 2],
            ['symptom' => 'Trembling', 'topic_id' => 2],
            ['symptom' => 'Feeling of impending doom', 'topic_id' => 2],
            ['symptom' => 'Panic attacks', 'topic_id' => 2],
            ['symptom' => 'Avoidance behavior', 'topic_id' => 2],
            ['symptom' => 'Nausea', 'topic_id' => 2],
            ['symptom' => 'Shortness of breath', 'topic_id' => 2],
            ['symptom' => 'Dizziness', 'topic_id' => 2],
            ['symptom' => 'Chest pain', 'topic_id' => 2],
            ['symptom' => 'Numbness or tingling', 'topic_id' => 2],
        
            // Depression (topic_id: 3)
            ['symptom' => 'Persistent sad mood', 'topic_id' => 3],
            ['symptom' => 'Loss of interest in activities', 'topic_id' => 3],
            ['symptom' => 'Changes in appetite', 'topic_id' => 3],
            ['symptom' => 'Changes in sleep patterns', 'topic_id' => 3],
            ['symptom' => 'Fatigue', 'topic_id' => 3],
            ['symptom' => 'Feelings of worthlessness', 'topic_id' => 3],
            ['symptom' => 'Difficulty concentrating', 'topic_id' => 3],
            ['symptom' => 'Thoughts of death or suicide', 'topic_id' => 3],
            ['symptom' => 'Irritability', 'topic_id' => 3],
            ['symptom' => 'Physical aches and pains', 'topic_id' => 3],
            ['symptom' => 'Feeling hopeless', 'topic_id' => 3],
            ['symptom' => 'Crying spells', 'topic_id' => 3],
            ['symptom' => 'Anxiety', 'topic_id' => 3],
            ['symptom' => 'Social withdrawal', 'topic_id' => 3],
            ['symptom' => 'Indecisiveness', 'topic_id' => 3],
            ['symptom' => 'Slowed speech and movements', 'topic_id' => 3],
            ['symptom' => 'Weight gain or loss', 'topic_id' => 3],
        
            // Post-traumatic stress disorder (topic_id: 4)
            ['symptom' => 'Intrusive thoughts', 'topic_id' => 4],
            ['symptom' => 'Flashbacks', 'topic_id' => 4],
            ['symptom' => 'Nightmares', 'topic_id' => 4],
            ['symptom' => 'Severe anxiety', 'topic_id' => 4],
            ['symptom' => 'Avoidance behaviors', 'topic_id' => 4],
            ['symptom' => 'Hypervigilance', 'topic_id' => 4],
            ['symptom' => 'Exaggerated startle response', 'topic_id' => 4],
            ['symptom' => 'Irritability', 'topic_id' => 4],
            ['symptom' => 'Memory problems', 'topic_id' => 4],
            ['symptom' => 'Emotional numbness', 'topic_id' => 4],
            ['symptom' => 'Guilt or shame', 'topic_id' => 4],
            ['symptom' => 'Anger outbursts', 'topic_id' => 4],
            ['symptom' => 'Difficulty sleeping', 'topic_id' => 4],
            ['symptom' => 'Trouble concentrating', 'topic_id' => 4],
            ['symptom' => 'Feeling detached from reality', 'topic_id' => 4],
            ['symptom' => 'Negative thoughts about oneself', 'topic_id' => 4],
            ['symptom' => 'Loss of interest in enjoyable activities', 'topic_id' => 4],
        
            // Hypertension (topic_id: 5)
            ['symptom' => 'Headaches', 'topic_id' => 5],
            ['symptom' => 'Shortness of breath', 'topic_id' => 5],
            ['symptom' => 'Nosebleeds', 'topic_id' => 5],
            ['symptom' => 'Flushing', 'topic_id' => 5],
            ['symptom' => 'Dizziness', 'topic_id' => 5],
            ['symptom' => 'Chest pain', 'topic_id' => 5],
            ['symptom' => 'Visual changes', 'topic_id' => 5],
            ['symptom' => 'Blood in urine', 'topic_id' => 5],
            ['symptom' => 'Irregular heartbeat', 'topic_id' => 5],
            ['symptom' => 'Severe anxiety', 'topic_id' => 5],
            ['symptom' => 'Fatigue', 'topic_id' => 5],
            ['symptom' => 'Confusion', 'topic_id' => 5],
            ['symptom' => 'Sweating', 'topic_id' => 5],
            ['symptom' => 'Tinnitus', 'topic_id' => 5],
            ['symptom' => 'Nausea', 'topic_id' => 5],
            ['symptom' => 'Vomiting', 'topic_id' => 5],
            ['symptom' => 'Blurred vision', 'topic_id' => 5],
        
            // Diabetes (topic_id: 6)
            ['symptom' => 'Increased thirst', 'topic_id' => 6],
            ['symptom' => 'Frequent urination', 'topic_id' => 6],
            ['symptom' => 'Extreme hunger', 'topic_id' => 6],
            ['symptom' => 'Unexplained weight loss', 'topic_id' => 6],
            ['symptom' => 'Presence of ketones in the urine', 'topic_id' => 6],
            ['symptom' => 'Fatigue', 'topic_id' => 6],
            ['symptom' => 'Irritability', 'topic_id' => 6],
            ['symptom' => 'Blurred vision', 'topic_id' => 6],
            ['symptom' => 'Slow-healing sores', 'topic_id' => 6],
            ['symptom' => 'Frequent infections', 'topic_id' => 6],
            ['symptom' => 'Darkened skin areas', 'topic_id' => 6],
            ['symptom' => 'Numbness or tingling in hands or feet', 'topic_id' => 6],
            ['symptom' => 'Inability to cope with sudden physical activity', 'topic_id' => 6],
            ['symptom' => 'Feeling very tired every day', 'topic_id' => 6],
            ['symptom' => 'Back and joint pains', 'topic_id' => 6],
            ['symptom' => 'Low confidence and self-esteem', 'topic_id' => 6],
            ['symptom' => 'Feeling isolated', 'topic_id' => 6],
            ['symptom' => 'Loss of libido', 'topic_id' => 6],
            ['symptom' => 'Difficulty sleeping', 'topic_id' => 6],
            ['symptom' => 'Skin problems', 'topic_id' => 6],
            ['symptom' => 'Gallstones', 'topic_id' => 6],
            ['symptom' => 'Heartburn', 'topic_id' => 6],
            ['symptom' => 'Depression', 'topic_id' => 6],
            ['symptom' => 'Swelling in legs and feet', 'topic_id' => 6],
            ['symptom' => 'Shortness of breath', 'topic_id' => 6],
        
            // Obesity (topic_id: 7)
            ['symptom' => 'Excess body fat', 'topic_id' => 7],
            ['symptom' => 'Difficulty with physical activity', 'topic_id' => 7],
            ['symptom' => 'Fatigue', 'topic_id' => 7],
            ['symptom' => 'Back pain', 'topic_id' => 7],
            ['symptom' => 'Joint pain', 'topic_id' => 7],
            ['symptom' => 'Sleep apnea', 'topic_id' => 7],
            ['symptom' => 'Snoring', 'topic_id' => 7],
            ['symptom' => 'Sweating', 'topic_id' => 7],
            ['symptom' => 'Skin problems', 'topic_id' => 7],
            ['symptom' => 'Gallstones', 'topic_id' => 7],
            ['symptom' => 'Heartburn', 'topic_id' => 7],
            ['symptom' => 'Low confidence and self-esteem', 'topic_id' => 7],
            ['symptom' => 'Feeling isolated', 'topic_id' => 7],
            ['symptom' => 'Loss of libido', 'topic_id' => 7],
            ['symptom' => 'Depression', 'topic_id' => 7],
            ['symptom' => 'Swelling in legs and feet', 'topic_id' => 7],
            ['symptom' => 'Shortness of breath', 'topic_id' => 7],
        
            // Sexual health (topic_id: 8)
            ['symptom' => 'Pain during sex', 'topic_id' => 8],
            ['symptom' => 'Erectile dysfunction', 'topic_id' => 8],
            ['symptom' => 'Decreased libido', 'topic_id' => 8],
            ['symptom' => 'Unusual discharge', 'topic_id' => 8],
            ['symptom' => 'Genital sores', 'topic_id' => 8],
            ['symptom' => 'Itching in genital area', 'topic_id' => 8],
            ['symptom' => 'Painful urination', 'topic_id' => 8],
            ['symptom' => 'Bleeding between periods', 'topic_id' => 8],
            ['symptom' => 'Testicular pain', 'topic_id' => 8],
            ['symptom' => 'Vaginal dryness', 'topic_id' => 8],
            ['symptom' => 'Lack of orgasm', 'topic_id' => 8],
            ['symptom' => 'Premature ejaculation', 'topic_id' => 8],
            ['symptom' => 'Painful intercourse', 'topic_id' => 8],
            ['symptom' => 'Urinary tract infections', 'topic_id' => 8],
            ['symptom' => 'Pelvic pain', 'topic_id' => 8],
            ['symptom' => 'Painful ejaculation', 'topic_id' => 8],
            ['symptom' => 'Swollen lymph nodes', 'topic_id' => 8],
        
            // Maternal health (topic_id: 9)
            ['symptom' => 'Nausea and vomiting', 'topic_id' => 9],
            ['symptom' => 'Frequent urination', 'topic_id' => 9],
            ['symptom' => 'Back pain', 'topic_id' => 9],
            ['symptom' => 'Swelling of ankles, fingers, and face', 'topic_id' => 9],
            ['symptom' => 'Fatigue', 'topic_id' => 9],
            ['symptom' => 'Shortness of breath', 'topic_id' => 9],
            ['symptom' => 'Heartburn', 'topic_id' => 9],
            ['symptom' => 'Constipation', 'topic_id' => 9],
            ['symptom' => 'Breast tenderness', 'topic_id' => 9],
            ['symptom' => 'Mood swings', 'topic_id' => 9],
            ['symptom' => 'Food cravings', 'topic_id' => 9],
            ['symptom' => 'Frequent headaches', 'topic_id' => 9],
            ['symptom' => 'Dizziness', 'topic_id' => 9],
            ['symptom' => 'Leg cramps', 'topic_id' => 9],
            ['symptom' => 'Varicose veins', 'topic_id' => 9],
            ['symptom' => 'Difficulty sleeping', 'topic_id' => 9],
            ['symptom' => 'Hemorrhoids', 'topic_id' => 9],
        
            // Cancer (topic_id: 10)
            ['symptom' => 'Unexplained weight loss', 'topic_id' => 10],
            ['symptom' => 'Fatigue', 'topic_id' => 10],
            ['symptom' => 'Pain', 'topic_id' => 10],
            ['symptom' => 'Skin changes', 'topic_id' => 10],
            ['symptom' => 'Changes in bowel habits', 'topic_id' => 10],
            ['symptom' => 'Persistent cough', 'topic_id' => 10],
            ['symptom' => 'Hoarseness', 'topic_id' => 10],
            ['symptom' => 'Difficulty swallowing', 'topic_id' => 10],
            ['symptom' => 'Unusual bleeding or discharge', 'topic_id' => 10],
            ['symptom' => 'Lumps or thickening under the skin', 'topic_id' => 10],
            ['symptom' => 'Night sweats', 'topic_id' => 10],
            ['symptom' => 'Fever', 'topic_id' => 10],
            ['symptom' => 'Itchy skin', 'topic_id' => 10],
            ['symptom' => 'Loss of appetite', 'topic_id' => 10],
            ['symptom' => 'Chronic pain', 'topic_id' => 10],
            ['symptom' => 'Jaundice', 'topic_id' => 10],
            ['symptom' => 'Bloating', 'topic_id' => 10],
        
            // Sickle cell disease (topic_id: 11)
            ['symptom' => 'Anemia', 'topic_id' => 11],
            ['symptom' => 'Episodes of pain', 'topic_id' => 11],
            ['symptom' => 'Swelling of hands and feet', 'topic_id' => 11],
            ['symptom' => 'Frequent infections', 'topic_id' => 11],
            ['symptom' => 'Delayed growth', 'topic_id' => 11],
            ['symptom' => 'Vision problems', 'topic_id' => 11],
            ['symptom' => 'Fatigue', 'topic_id' => 11],
            ['symptom' => 'Jaundice', 'topic_id' => 11],
            ['symptom' => 'Dizziness', 'topic_id' => 11],
            ['symptom' => 'Shortness of breath', 'topic_id' => 11],
            ['symptom' => 'Chest pain', 'topic_id' => 11],
            ['symptom' => 'Pale skin', 'topic_id' => 11],
            ['symptom' => 'Leg ulcers', 'topic_id' => 11],
            ['symptom' => 'Stroke', 'topic_id' => 11],
            ['symptom' => 'Joint pain', 'topic_id' => 11],
            ['symptom' => 'Priapism', 'topic_id' => 11],
            ['symptom' => 'Enlarged spleen', 'topic_id' => 11],
            // Other (topic_id: 12)
            ['symptom' => 'Other', 'topic_id' => 12],
            
        
        ];

        foreach ($symptoms as $symptom) {
            DB::table('physical_symptoms')->insert([
                'symptom' => $symptom['symptom'],
                'topic_id' => $symptom['topic_id'],
                'type'=>($symptom['topic_id']==12)?2:1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
