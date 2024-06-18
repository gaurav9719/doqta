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
            ['name' => 'Doctor of Medicine (MD)', 'type' => 1, 'short_name' => 'MD'],
            ['name' => 'Doctor of Osteopathic Medicine (DO)', 'type' => 1, 'short_name' => 'DO'],
            ['name' => 'Doctor of Dental Surgery (DDS)', 'type' => 1, 'short_name' => 'DDS'],
            ['name' => 'Doctor of Dental Medicine (DMD)', 'type' => 1, 'short_name' => 'DMD'],
            ['name' => 'Doctor of Chiropractic (DC)', 'type' => 1, 'short_name' => 'DC'],
            ['name' => 'Doctor of Optometry (OD)', 'type' => 1, 'short_name' => 'OD'],
            ['name' => 'Doctor of Pharmacy (PharmD)', 'type' => 1, 'short_name' => 'PharmD'],
            ['name' => 'Doctor of Veterinary Medicine (DVM)', 'type' => 1, 'short_name' => 'DVM'],
            ['name' => 'Doctor of Podiatric Medicine (DPM)', 'type' => 1, 'short_name' => 'DPM'],
            ['name' => 'Bachelor of Medicine, Bachelor of Surgery (MBBS)', 'type' => 1, 'short_name' => 'MBBS'],
            ['name' => 'Doctor of Naturopathic Medicine (ND)', 'type' => 1, 'short_name' => 'ND'],
            ['name' => 'Doctor of Physical Therapy (DPT)', 'type' => 1, 'short_name' => 'DPT'],
            ['name' => 'Doctor of Occupational Therapy (DOT)', 'type' => 1, 'short_name' => 'DOT'],
            ['name' => 'Doctor of Osteopathic Medicine)', 'type' => 1, 'short_name' => ''],
            ['name' => 'Doctor of Public Health (DrPH)', 'type' => 1, 'short_name' => 'DrPH'],
            ['name' => 'Registered Nurse (RN)', 'type' => 1, 'short_name' => 'RN'],
            ['name' => 'Licensed Practical Nurse (LPN)', 'type' => 1, 'short_name' => 'LPN'],
            ['name' => 'Certified Nursing Assistant (CNA)', 'type' => 1, 'short_name' => 'CNA'],
            ['name' => 'Nurse Practitioner (NP)', 'type' => 1, 'short_name' => 'NP'],
            ['name' => 'Certified Registered Nurse Anesthetist (CRNA)', 'type' => 1, 'short_name' => 'CRNA'],
            ['name' => 'Certified Nurse Midwife (CNM)', 'type' => 1, 'short_name' => 'CNM'],
            ['name' => 'Licensed Vocational Nurse (LVN)', 'type' => 1, 'short_name' => 'LVN'],
            ['name' => 'Physician Assistant (PA)', 'type' => 1, 'short_name' => 'PA'],
            ['name' => 'Certified Medical Assistant (CMA)', 'type' => 1, 'short_name' => 'CMA'],
            ['name' => 'Certified Nursing Midwife (CNM)', 'type' => 1, 'short_name' => 'CNM'],
            ['name' => 'Doctor of Philosophy (PhD)', 'type' => 2, 'short_name' => 'PhD'],
            ['name' => 'Doctor of Nursing Practice (DNP)', 'type' => 1, 'short_name' => 'DNP'],
            ['name' => 'Doctor of Audiology (AuD)', 'type' => 1, 'short_name' => 'AuD'],
            ['name' => 'Doctor of Social Work (DSW)', 'type' => 1, 'short_name' => 'DSW'],
            ['name' => 'Doctor of Public Administration (DPA)', 'type' => 1, 'short_name' => 'DPA'],
            ['name' => 'Doctor of Psychology (PsyD)', 'type' => 1, 'short_name' => 'PsyD'],
            ['name' => 'Doctor of Science in Physical Therapy (DScPT)', 'type' => 1, 'short_name' => 'DScPT'],
            ['name' => 'Doctor of Science in Occupational Therapy (OTD)', 'type' => 1, 'short_name' => 'OTD'],
            ['name' => 'Doctor of Health Administration (DHA)', 'type' => 1, 'short_name' => 'DHA'],
            ['name' => 'Doctor of Health Science (DHSc)', 'type' => 1, 'short_name' => 'DHSc'],
            ['name' => 'Doctor of Education in Nursing Education (EdD)', 'type' => 1, 'short_name' => 'EdD'],
            ['name' => 'Other', 'type' => 2, 'short_name' => ''],
        ];
        
        // Add more credentials as needed
        
        

        foreach ($credentials as $credential) {
            DB::table('medical_credentials')->insert([
                'name' => $credential['name'],
                'short_name' => $credential['short_name'],
                'is_active'=>1,
                'type'=>$credential['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
