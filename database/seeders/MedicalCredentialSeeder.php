<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalCredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $credentials = [
            ['name' => 'Doctor of Medicine (MD)', 'type' => 1],
            ['name' => 'Doctor of Osteopathic Medicine (DO)', 'type' => 1],
            ['name' => 'Doctor of Dental Surgery (DDS)', 'type' => 1],
            ['name' => 'Doctor of Dental Medicine (DMD)', 'type' => 1],
            ['name' => 'Doctor of Chiropractic (DC)', 'type' => 1],
            ['name' => 'Doctor of Optometry (OD)', 'type' => 1],
            ['name' => 'Doctor of Pharmacy (PharmD)', 'type' => 1],
            ['name' => 'Doctor of Veterinary Medicine (DVM)', 'type' => 1],
            ['name' => 'Doctor of Podiatric Medicine (DPM)', 'type' => 1],
            ['name' => 'Bachelor of Medicine, Bachelor of Surgery (MBBS)', 'type' => 1],
            ['name' => 'Doctor of Naturopathic Medicine (ND)', 'type' => 1],
            ['name' => 'Doctor of Physical Therapy (DPT)', 'type' => 1],
            ['name' => 'Doctor of Occupational Therapy (DOT)', 'type' => 1],
            ['name' => 'Doctor of Osteopathic Medicine)', 'type' => 1],
            ['name' => 'Doctor of Public Health (DrPH)', 'type' => 1],
            ['name' => 'Registered Nurse (RN)', 'type' => 1],
            ['name' => 'Licensed Practical Nurse (LPN)', 'type' => 1],
            ['name' => 'Certified Nursing Assistant (CNA)', 'type' => 1],
            ['name' => 'Nurse Practitioner (NP)', 'type' => 1],
            ['name' => 'Certified Registered Nurse Anesthetist (CRNA)', 'type' => 1],
            ['name' => 'Certified Nurse Midwife (CNM)', 'type' => 1],
            ['name' => 'Licensed Vocational Nurse (LVN)', 'type' => 1],
            ['name' => 'Physician Assistant (PA)', 'type' => 1],
            ['name' => 'Certified Medical Assistant (CMA)', 'type' => 1],
            ['name' => 'Certified Nursing Midwife (CNM)', 'type' => 1],
            ['name' => 'Doctor of Philosophy (PhD)', 'type' => 2],
            ['name' => 'Doctor of Nursing Practice (DNP)', 'type' => 1],
            ['name' => 'Doctor of Audiology (AuD)', 'type' => 1],
            ['name' => 'Doctor of Social Work (DSW)', 'type' => 1],
            ['name' => 'Doctor of Public Administration (DPA)', 'type' => 1],
            ['name' => 'Doctor of Psychology (PsyD)', 'type' => 1],
            ['name' => 'Doctor of Science in Physical Therapy (DScPT)', 'type' => 1],
            ['name' => 'Doctor of Science in Occupational Therapy (OTD)', 'type' => 1],
            ['name' => 'Doctor of Health Administration (DHA)', 'type' => 1],
            ['name' => 'Doctor of Health Science (DHSc)', 'type' => 1],
            ['name' => 'Doctor of Education in Nursing Education (EdD)', 'type' => 1],
            ['name' => 'Other', 'type' => 2],
            // Add more credentials as needed
        ];
        

        foreach ($credentials as $credential) {
            DB::table('medical_credentials')->insert([
                'name' => $credential['name'],
                'is_active'=>1,
                'type'=>$credential['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
