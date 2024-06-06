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
        Schema::create('chat_insight_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('insight_id');
            $table->unsignedBigInteger('entry_id');
            $table->foreign('report_id')->references('id')->on('journal_reports')->cascadeOnDelete();
            $table->foreign('insight_id')->references('id')->on('chat_insights')->cascadeOnDelete();
            $table->foreign('entry_id')->references('id')->on('ai_messages')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_insight_entries');
    }
};
