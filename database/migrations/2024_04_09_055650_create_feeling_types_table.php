<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\FeelingTypeSeeder;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feeling_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feeling_id')->nullable();
            $table->string('name')->nullable();
            // Define foreign key constraint
            $table->foreign('feeling_id')->references('id')->on('feelings')->onDelete('cascade');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });

        Artisan::call('db:seed',['--class'=>FeelingTypeSeeder::class]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
