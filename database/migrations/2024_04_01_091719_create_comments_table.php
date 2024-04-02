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
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('post_id');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->text('comment');
            $table->tinyInteger('comment_type')->default(1)->comment('1: text, 2: video, 3: link, 4: emoji');
            $table->boolean('is_active')->default(true)->comment('1: active, 0: inactive');
            $table->timestamps();

            // Indexes
            $table->index('user_id'); // Index added for user_id column
            $table->index(['post_id', 'parent_id']);
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
