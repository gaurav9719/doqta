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
        Schema::create('my_rosters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('roster_id')->nullable()->comment('match user id');
            $table->unsignedBigInteger('recruiter_id')->nullable()->comment('who choose for dater');
            $table->unsignedBigInteger('my_team_member_id')->nullable()->comment('primary key of  my team member');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('roster_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('recruiter_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('my_team_member_id')->references('id')->on('my_team_members')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_rosters');
    }
};
