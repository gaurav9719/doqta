<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
    
        $specialties = [
            ['name' => 'Allergy & Immunology', 'type' => 1],
            ['name' => 'Anesthesiology', 'type' => 1],
            ['name' => 'Anatomic Pathology', 'type' => 1],
            ['name' => 'Cardiology', 'type' => 1],
            ['name' => 'Clinical Pathology', 'type' => 1],
            ['name' => 'Dental Public Health', 'type' => 1],
            ['name' => 'Dermatology', 'type' => 1],
            ['name' => 'Diagnostic Radiology', 'type' => 1],
            ['name' => 'Emergency Medical Services (EMS)', 'type' => 1],
            ['name' => 'Emergency Medicine', 'type' => 1],
            ['name' => 'Endocrinology', 'type' => 1],
            ['name' => 'Family Medicine', 'type' => 1],
            ['name' => 'Gastroenterology', 'type' => 1],
            ['name' => 'Geriatrics', 'type' => 1],
            ['name' => 'Hematology', 'type' => 1],
            ['name' => 'Infectious Diseases', 'type' => 1],
            ['name' => 'Internal Medicine', 'type' => 1],
            ['name' => 'Medical Genetics', 'type' => 1],
            ['name' => 'Medical Toxicology', 'type' => 1],
            ['name' => 'Neonatology', 'type' => 1],
            ['name' => 'Nephrology', 'type' => 1],
            ['name' => 'Neurology', 'type' => 1],
            ['name' => 'Nuclear Medicine', 'type' => 1],
            ['name' => 'Obstetrics & Gynecology', 'type' => 1],
            ['name' => 'Oncology', 'type' => 1],
            ['name' => 'Ophthalmology', 'type' => 1],
            ['name' => 'Orthopedic Surgery', 'type' => 1],
            ['name' => 'Otolaryngology (ENT)', 'type' => 1],
            ['name' => 'Pain Management', 'type' => 1],
            ['name' => 'Pain Medicine', 'type' => 1],
            ['name' => 'Pathology', 'type' => 1],
            ['name' => 'Pediatric Cardiology', 'type' => 1],
            ['name' => 'Pediatric Endocrinology', 'type' => 1],
            ['name' => 'Pediatric Gastroenterology', 'type' => 1],
            ['name' => 'Pediatric Hematology-Oncology', 'type' => 1],
            ['name' => 'Pediatric Infectious Diseases', 'type' => 1],
            ['name' => 'Pediatric Nephrology', 'type' => 1],
            ['name' => 'Pediatric Pulmonology', 'type' => 1],
            ['name' => 'Pediatric Rheumatology', 'type' => 1],
            ['name' => 'Pediatrics', 'type' => 1],
            ['name' => 'Physical Medicine & Rehabilitation', 'type' => 1],
            ['name' => 'Plastic Surgery', 'type' => 1],
            ['name' => 'Primary Care', 'type' => 1],
            ['name' => 'Psychiatry', 'type' => 1],
            ['name' => 'Pulmonology', 'type' => 1],
            ['name' => 'Radiology', 'type' => 1],
            ['name' => 'Reproductive Endocrinology & Infertility', 'type' => 1],
            ['name' => 'Rheumatology', 'type' => 1],
            ['name' => 'Sleep Medicine', 'type' => 1],
            ['name' => 'Sports Medicine', 'type' => 1],
            ['name' => 'Surgery', 'type' => 1],
            ['name' => 'Surgical Critical Care', 'type' => 1],
            ['name' => 'Transplant Hepatology', 'type' => 1],
            ['name' => 'Urology', 'type' => 1],
            ['name' => 'Others', 'type' => 2],
            ['name' => 'Biology', 'type' => 2],
            ['name' => 'Public Health', 'type' => 2],
        ];
        
        foreach ($specialties as $specialty) {
            DB::table('specialties')->insert([
                'name' => $specialty['name'],
                'type' => $specialty['type'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
