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
        Schema::create('chat_insights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->tinyInteger('type')->nullable()->comment('1=insights, 2=suggestion');
            $table->string('details');
            $table->foreign('report_id')->references('id')->on('journal_reports')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_insights');
    }
};
