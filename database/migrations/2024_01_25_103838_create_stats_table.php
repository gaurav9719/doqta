<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\StatsSeeder;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('question')->nullable();
            $table->tinyInteger('min_value')->default(0);
            $table->tinyInteger('max_value')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
        Artisan::call('db:seed', ['--class' => StatsSeeder::class]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats');
    }
};
