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
        Schema::create('user_quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->integer('community_posts')->default(0);
            $table->integer('chatbot_messages')->default(0);
            $table->integer('journal_entries')->default(0);
            $table->integer('rewrite_with_ai')->default(0);
            $table->integer('friend_requests')->default(0);
            $table->integer('post_comments')->default(0);
            $table->integer('community_join_requests')->default(0);
            $table->integer('is_active')->default(1)->comment('0:inactive,1:active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quotas');
    }
};
