<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Artisan;
use Database\Seeders\PhysicalSymptomSeeder;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('physical_symptoms', function (Blueprint $table) {
            $table->id();
            $table->string('symptom')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
        Artisan::call('db:seed',['--class'=>PhysicalSymptomSeeder::class]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_symptoms');
    }
};
