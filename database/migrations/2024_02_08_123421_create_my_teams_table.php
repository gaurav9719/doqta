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
        Schema::create('my_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recruiter_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->foreign('recruiter_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('member_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('team_name')->nullable();
            $table->tinyInteger('team_type')->default(1)->comment('1:invite,2:ghost,3:AI coach');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_teams');
    }
};
