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
        Schema::create('inboxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->unsignedBigInteger('is_user1_trash')->nullable();
            $table->unsignedBigInteger('is_user2_trash')->nullable();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('is_user1_trash')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('is_user2_trash')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
            $table->index(['sender_id', 'receiver_id','is_active']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inboxes');
        
    }
};
