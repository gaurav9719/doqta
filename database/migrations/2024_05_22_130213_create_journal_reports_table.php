<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->json('report')->nullable();
            $table->tinyInteger('type')->nullable()->comment('1=insights_and_suggestion, 2=report');
            $table->foreign('journal_id')->references('id')->on('journals')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_reports');
    }
};
