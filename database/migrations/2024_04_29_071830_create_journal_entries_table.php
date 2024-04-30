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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('content')->nullable();
            $table->string('media')->nullable();
            $table->string('audio')->nullable();
            $table->string('link')->nullable();
            $table->unsignedBigInteger('feeling_id')->nullable();
            $table->tinyInteger('pain')->default(0);
            $table->date('journal_on')->default(now());
            $table->boolean('is_favorite')->default(0)->comment('1:yes,0:no');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
            $table->foreign('feeling_id')->references('id')->on('feelings')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('user_id');    // Index for user_id column
            $table->index('journal_id');  // Index for journal_id column
            $table->index('journal_on');  // Index for journal_id column
            $table->index('feeling_id');  // Index for journal_id column
            $table->index('pain');  // Index for journal_id column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
