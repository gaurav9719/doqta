<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\RoleSeeder;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            
            $table->id()->comment("1:admin,2:dater,3:recruiter,4:Roaster AI");
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(1)->comment('0:inactive,1:active');
            $table->timestamps();

        });

        Artisan::call('db:seed', ['--class' => RoleSeeder::class]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
