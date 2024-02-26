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
        Schema::create('user_swipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('swiping_user_id')->nullable()->comment('User performing the swipe action');
            $table->unsignedBigInteger('swiped_user_id')->nullable()->comment('User whose profile is being swiped on');
            $table->boolean('swipe_type')->nullable()->comment('0: reject, 1: accept/add to team');
            $table->unsignedBigInteger('role_id')->nullable()->comment('ID of the role (if applicable)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // Foreign key constraints
            $table->foreign('swiping_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('swiped_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
            // Indexes
            $table->index(['swiping_user_id', 'swiped_user_id']);
            $table->index('role_id');

        });

        // Foreign keys
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_swipes');
    }
};
