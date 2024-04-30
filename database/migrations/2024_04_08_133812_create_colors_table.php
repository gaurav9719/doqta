<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\ColorSeeder;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('hex_code')->unique();
            $table->float('opacity')->define('1.0');
            $table->boolean('is_active')->comment('0:inactive,1:active')->default(1);
            $table->timestamps();
        });

        Artisan::call('db:seed',['--class'=>ColorSeeder::class]);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
