<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\PronounsSeeder;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pronouns', function (Blueprint $table) {
            $table->id();
            $table->string('subjective')->nullable();
            $table->string('objective')->nullable();
            $table->string('possessive')->nullable();
            $table->string('possessive')->nullable();
            $table->string('possessive_plural')->nullable();
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });

        Artisan::call('db:seed',['--class'=>PronounsSeeder::class]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pronouns');
    }
};
