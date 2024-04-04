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
        Schema::create('hidden_posts', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('user_id')->nullable(); // Make user_id nullable
            $table->unsignedBigInteger('post_id')->nullable(); // Make post_id nullable
            $table->timestamps();
            // Add indexes to the user_id and post_id columns for optimization
            $table->index('user_id');
            $table->index('post_id');
            // Add foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hidden_posts');
    }
};
