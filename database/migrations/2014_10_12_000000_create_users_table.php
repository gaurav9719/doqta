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
            $table->id()->index();
            $table->string('social_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('user_name')->nullable()->index();
            $table->string('email')->nullable()->unique()->index();
            $table->string('password')->nullable();
            $table->date('dob')->nullable();
            $table->integer('country_code')->nullable();
            $table->string('phone_no',20)->nullable()->index();
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
            $table->string('profile')->nullable();
            $table->string('cover')->nullable();
            $table->string('bio')->nullable();
            $table->tinyInteger('is_muted')->default(0)->comment('0:active,1:muted');
            $table->string('otp')->nullable();
            $table->dateTime('otp_expiry_time')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('role')->comment('1=user, 3=admin')->default(1)->index();
            $table->rememberToken();
            $table->tinyInteger('is_active')->default(1)->comment('0:inactive,1 active,2deleted');
            $table->timestamps();

        });

        DB::table('users')->insert([
            
                'name' => 'Doqta',
                'email' => 'doqta@app.com',
                'password' => Hash::make('Doqta@2024@(+)'),
                'created_at' => now(),
                'role'=>3,
                'updated_at' => now()
            ]);

            DB::table('users')->insert([

                'name' => 'AI',
                'user_name' => 'AI',
                'email' => "doqtaAi@doqtaapp.com",
                'password' =>null,
                'profile' => 'app_icon/ai.png',
                'role'=>4,
                'created_at' => now(),
                'updated_at' => now()
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
