<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('user_name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->date('dob')->nullable();
            $table->string('reference_code')->nullable();
            $table->tinyInteger("register_role_type")->default(1);
            $table->tinyInteger("current_role_id")->default(1);
            $table->tinyInteger("recruit_type_asdater")->default(0)->comment('1:invite friend,2ghost coach,3:roserAI');
            $table->tinyInteger("recruit_type_asrecruiter")->default(0)->comment('1:invite friend,2ghost coach,3:roserAI');
            $table->integer('country_code')->nullable();
            $table->string('phone_no',20)->nullable();
            $table->integer('country_id')->default(0);
            $table->integer('state_id')->default(0);
            $table->integer('city_id')->default(0);
            $table->string('zipcode')->nullable();
            $table->double('lat',8, 3)->nullable();
            $table->double('long',8, 3)->nullable();
            $table->tinyInteger('gender')->default(1)->comment('1:male,2:female,3:other');
            $table->tinyInteger('login_type')->default(0)->comment('0:normal,1:gmail,2:apple,3:facebook');
            $table->tinyInteger('device_type')->default(0)->comment('1:ios,2:andriod');
            $table->string('device_token')->nullable();
            $table->string('profile_pic')->nullable();
            $table->string('otp')->nullable();
            $table->dateTime('otp_expiry_time')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->tinyInteger('is_active')->default(1)->comment('0:inactive,1 active,2deleted');
        });

        DB::table('users')->insert([
            [
                'name' => 'Roster Admin',
                'email' => 'admin@hosterapp.com',
                'password' => Hash::make('Roster@2024@(+)'),
                'register_role_type'=>1,
                'current_role_id'=>1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'name' => 'AI User',
                'email' => 'ai@example.com',
                'password' => Hash::make('Roster_ai@2024#$'),
                'register_role_type'=>4,
                'current_role_id'=>4,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
