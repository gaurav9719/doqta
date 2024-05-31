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
            $table->unsignedBigInteger('journal_id')->nullable()->index();
            $table->unsignedBigInteger('ai_thread_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('start_date')->nullable()->index();
            $table->string('end_date')->nullable()->index();
            $table->string('ids_count')->nullable();
            $table->string('start_id')->nullable();
            $table->string('end_id')->nullable();
            $table->json('report')->nullable();
            $table->tinyInteger('type')->nullable()->comment('1=insights_and_suggestion, 2=report,3 chat insight');
            $table->tinyInteger('report_type')->nullable()->comment('1=journal_report, 2=chat_report');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
            $table->foreign('ai_thread_id')->references('id')->on('ai_threads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
