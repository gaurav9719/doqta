<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PointSystem;
use Illuminate\Support\Str;

class PointSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        #------ RECRUITER -------#
       #------ RECRUITER -------#
        PointSystem::insert([
            ['activity_name' => 'Invite a dater to join The Roster', 'point' => 1, 'user_role' => 3, 'slug' => Str::slug('Invite a dater to join The Roster'), 'keyword' => "Invite Dater"],
            ['activity_name' => 'Dater matches with your recruit', 'point' => 2, 'user_role' => 3, 'slug' => Str::slug('Dater matches with your recruit'), 'keyword' => ""],
            ['activity_name' => 'Dater adds your recruit to their roster', 'point' => 10, 'user_role' => 3, 'slug' => Str::slug('Dater adds your recruit to their roster'), 'keyword' => ""],
            ['activity_name' => 'Dater sends a first message to a match', 'point' => 15, 'user_role' => 3, 'slug' => Str::slug('Dater sends a first message to a match'), 'keyword' => ""],
            ['activity_name' => 'Dater goes on a date with match', 'point' => 30, 'user_role' => 3, 'slug' => Str::slug('Dater goes on a date with match'), 'keyword' => ""],
        ]);

        #------ DATER -------#
        PointSystem::insert([
            ['activity_name' => 'Invite a recruiter to join The Roster', 'point' => 1, 'user_role' => 2, 'slug' => Str::slug('Invite a recruiter to join The Roster'), 'keyword' => ""],
            ['activity_name' => 'Match with someone', 'point' => 2, 'user_role' => 2, 'slug' => Str::slug('Match with someone'), 'keyword' => ""],
            ['activity_name' => 'Move a match up on your roster', 'point' => 10, 'user_role' => 2, 'slug' => Str::slug('Move a match up on your roster'), 'keyword' => ""],
            ['activity_name' => 'Send a first message to a match', 'point' => 15, 'user_role' => 2, 'slug' => Str::slug('Send a first message to a match'), 'keyword' => ""],
            ['activity_name' => 'Go on a date with a match', 'point' => 30, 'user_role' => 2, 'slug' => Str::slug('Go on a date with a match'), 'keyword' => ""],
        ]);


    }
}
