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
        Schema::create('role_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // The user receiving the invitation
            $table->unsignedBigInteger('inviter_id')->nullable(); // The user sending the invitation
            $table->enum('role', ['owner', 'moderator','admin','user'])->nullable();
            $table->boolean('accepted')->nullable(); // NULL means pending, TRUE means accepted, FALSE means rejected
            $table->timestamps();
            // Setting up foreign key constraints
            $table->foreign('community_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_invitations');
    }
};
