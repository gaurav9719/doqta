<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Color;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            'F99797', 'F6EADF', 'F5E38B66', '5DB86F4D', 'DEEAEB', 
            'ACCFF666', '70F2FA4D', 'E8E6FD', '904BFF33', 'E597F933', 
            '222D654D', 'DDDDDD'
        ];

        foreach ($colors as $color) {
            Color::create(['hex_code' => $color]);
        }
    }
}
