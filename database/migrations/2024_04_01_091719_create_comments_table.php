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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('mention_user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('mention_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade')->index();
            $table->text('comment')->nullable();
            $table->tinyInteger('comment_type')->default(1)->comment('1: text, 2: video, 3: link, 4: emoji');
            $table->boolean('is_active')->default(true)->comment('1: active, 0: inactive');
            $table->timestamps();
            // Indexes
            $table->index(['user_id', 'parent_id']); // Index added
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
