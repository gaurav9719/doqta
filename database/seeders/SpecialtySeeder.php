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
            ['name' => 'Allergy & Immunology'],
            ['name' => 'Anesthesiology'],
            ['name' => 'Anatomic Pathology'],
            ['name' => 'Cardiology'],
            ['name' => 'Clinical Pathology'],
            ['name' => 'Dental Public Health'],
            ['name' => 'Dermatology'],
            ['name' => 'Diagnostic Radiology'],
            ['name' => 'Emergency Medical Services (EMS)'],
            ['name' => 'Emergency Medicine'],
            ['name' => 'Endocrinology'],
            ['name' => 'Family Medicine'],
            ['name' => 'Gastroenterology'],
            ['name' => 'Geriatrics'],
            ['name' => 'Hematology'],
            ['name' => 'Infectious Diseases'],
            ['name' => 'Internal Medicine'],
            ['name' => 'Medical Genetics'],
            ['name' => 'Medical Toxicology'],
            ['name' => 'Neonatology'],
            ['name' => 'Nephrology'],
            ['name' => 'Neurology'],
            ['name' => 'Nuclear Medicine'],
            ['name' => 'Obstetrics & Gynecology'],
            ['name' => 'Oncology'],
            ['name' => 'Ophthalmology'],
            ['name' => 'Orthopedic Surgery'],
            ['name' => 'Otolaryngology (ENT)'],
            ['name' => 'Pain Management'],
            ['name' => 'Pain Medicine'],
            ['name' => 'Pathology'],
            ['name' => 'Pediatric Cardiology'],
            ['name' => 'Pediatric Endocrinology'],
            ['name' => 'Pediatric Gastroenterology'],
            ['name' => 'Pediatric Hematology-Oncology'],
            ['name' => 'Pediatric Infectious Diseases'],
            ['name' => 'Pediatric Nephrology'],
            ['name' => 'Pediatric Pulmonology'],
            ['name' => 'Pediatric Rheumatology'],
            ['name' => 'Pediatrics'],
            ['name' => 'Physical Medicine & Rehabilitation'],
            ['name' => 'Plastic Surgery'],
            ['name' => 'Primary Care'],
            ['name' => 'Psychiatry'],
            ['name' => 'Pulmonology'],
            ['name' => 'Radiology'],
            ['name' => 'Reproductive Endocrinology & Infertility'],
            ['name' => 'Rheumatology'],
            ['name' => 'Sleep Medicine'],
            ['name' => 'Sports Medicine'],
            ['name' => 'Surgery'],
            ['name' => 'Surgical Critical Care'],
            ['name' => 'Transplant Hepatology'],
            ['name' => 'Urology'],
        ];
        

        foreach ($specialties as $specialty) {
            DB::table('specialties')->insert([
                'name' => $specialty['name'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
