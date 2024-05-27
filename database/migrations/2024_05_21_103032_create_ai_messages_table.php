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
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id')->index();
            $table->foreign('inbox_id')->references('id')->on('ai_threads')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('participant')->nullable();
            $table->text('message')->nullable();
            $table->text('media')->nullable();
            $table->integer('message_type')->default(1)->comment('1:text,2image');
            $table->tinyInteger('is_user1_trash')->default(0)->comment('0:not,1:delete');
            $table->tinyInteger('is_user2_trash')->default(0)->comment('0:not,1:delete');
            $table->tinyInteger('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
    
};
