<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['type'=>1,'name' => 'Free Trial', 'price' => null, 'currency' => 'USD', 'currency_symbol' => '$', 'duration' => '30', 'is_active' => 1],
            ['type'=>2, 'name' => 'Monthly', 'price' => '4.99', 'currency' => 'USD', 'currency_symbol' => '$', 'duration' => '30', 'is_active' => 1],
            ['type'=>3,'name' => 'Yearly', 'price' => '19.99', 'currency' => 'USD', 'currency_symbol' => '$', 'duration' => '365', 'is_active' => 1],
            ['type'=>4, 'name' => 'Corporate', 'price' => '0', 'currency' => 'USD', 'currency_symbol' => '$', 'duration' => '', 'is_active' => 1],
        ];
        foreach ($plans as $plan) {
            DB::table('plans')->insert([
                'type' => $plan['type'],
                'name' => $plan['name'],
                'price' => $plan['price'],
                'currency' => $plan['currency'],
                'currency_symbol' => $plan['currency_symbol'],
                'duration' => $plan['duration'],
                'is_active' => $plan['is_active'],
            ]);
        }
    }
}
