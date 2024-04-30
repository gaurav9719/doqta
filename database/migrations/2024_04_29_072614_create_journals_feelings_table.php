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
        Schema::create('journals_feelings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('feeling_type')->default(0);
            $table->foreign('feeling_type')->references('id')->on('feeling_types')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
            $table->index('journal_entry_id');    // Index for user_id column
            $table->index('is_active');  // Index for journal_id column
            $table->index('feeling_type');  // Index for journal_id column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals_feelings');
    }
};
