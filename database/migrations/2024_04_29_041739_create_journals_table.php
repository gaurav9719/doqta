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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->string('writing_for')->nullable();
            $table->unsignedBigInteger('color')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('topic_id')->references('id')->on('journal_topics')->onDelete('cascade');
            $table->foreign('color')->references('id')->on('colors')->onDelete('cascade');
            $table->date('entry_date')->default(now());
            $table->boolean('is_favorite')->default(0)->comment('1:yes,0:no');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
