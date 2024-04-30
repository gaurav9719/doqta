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
        $colorsWithOpacity = [
            ["hex_code" => "#F99797", "opacity" => 0.4],
            ["hex_code" => "#F6EADF", "opacity" => 1.0],
            ["hex_code" => "#F5E38B", "opacity" => 0.4],
            ["hex_code" => "#5DB86F", "opacity" => 0.3],
            ["hex_code" => "#DEEAEB", "opacity" => 1.0],
            ["hex_code" => "#ACCFF6", "opacity" => 0.4],
            ["hex_code" => "#70F2FA", "opacity" => 0.3],
            ["hex_code" => "#E8E6FD", "opacity" => 1.0],
            ["hex_code" => "#904BFF", "opacity" => 0.2],
            ["hex_code" => "#E597F9", "opacity" => 0.2],
            ["hex_code" => "#222D65", "opacity" => 0.3],
            ["hex_code" => "#DDDDDD", "opacity" => 1.0],
        ];
        
        foreach ($colorsWithOpacity as $color) {
            
            Color::create([
                'hex_code' => $color['hex_code'],
                'opacity' => $color['opacity']
            ]);
        }
        
    }
}
