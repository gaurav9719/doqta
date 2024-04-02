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
            ['name' => 'Doctor of Medicine (MD)'],
            ['name' => 'Doctor of Osteopathic Medicine (DO)'],
            ['name' => 'Doctor of Dental Surgery (DDS)'],
            ['name' => 'Doctor of Dental Medicine (DMD)'],
            ['name' => 'Doctor of Chiropractic (DC)'],
            ['name' => 'Doctor of Optometry (OD)'],
            ['name' => 'Doctor of Pharmacy (PharmD)'],
            ['name' => 'Doctor of Veterinary Medicine (DVM)'],
            ['name' => 'Doctor of Podiatric Medicine (DPM)'],
            ['name' => 'Bachelor of Medicine, Bachelor of Surgery (MBBS)'],
            ['name' => 'Doctor of Naturopathic Medicine (ND)'],
            ['name' => 'Doctor of Physical Therapy (DPT)'],
            ['name' => 'Doctor of Occupational Therapy (DOT)'],
            ['name' => 'Doctor of Osteopathic Medicine/Doctor of Philosophy (DO/PhD)'],
            ['name' => 'Doctor of Public Health (DrPH)'],
            ['name' => 'Registered Nurse (RN)'],
            ['name' => 'Licensed Practical Nurse (LPN)'],
            ['name' => 'Certified Nursing Assistant (CNA)'],
            ['name' => 'Nurse Practitioner (NP)'],
            ['name' => 'Certified Registered Nurse Anesthetist (CRNA)'],
            ['name' => 'Certified Nurse Midwife (CNM)'],
            ['name' => 'Licensed Vocational Nurse (LVN)'],
            ['name' => 'Physician Assistant (PA)'],
            ['name' => 'Certified Medical Assistant (CMA)'],
            ['name' => 'Certified Nursing Midwife (CNM)'],
            ['name' => 'Doctor of Philosophy (PhD) in Nursing'],
            ['name' => 'Doctor of Nursing Practice (DNP)'],
            ['name' => 'Doctor of Audiology (AuD)'],
            ['name' => 'Doctor of Social Work (DSW)'],
            ['name' => 'Doctor of Public Administration (DPA)'],
            ['name' => 'Doctor of Psychology (PsyD)'],
            ['name' => 'Doctor of Science in Physical Therapy (DScPT)'],
            ['name' => 'Doctor of Science in Occupational Therapy (OTD)'],
            ['name' => 'Doctor of Health Administration (DHA)'],
            ['name' => 'Doctor of Health Science (DHSc)'],
            ['name' => 'Doctor of Education in Nursing Education (EdD)'],
            // Add more credentials as needed
        ];

        foreach ($credentials as $credential) {
            DB::table('medical_credentials')->insert([
                'name' => $credential['name'],
                'is_active'=>1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
