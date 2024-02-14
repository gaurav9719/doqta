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
        Schema::create('my_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable()->comment('team owner id');
            $table->unsignedBigInteger('dater_id')->nullable()->comment('rand user id');
            $table->foreign('team_id')->references('id')->on('my_teams')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dater_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('member_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('recruiter_type')->nullable()->comment('1:invite friend,2 ghost coach,3 Roster AI');
            $table->boolean('request_status')->default(0)->comment('0:pending,1:add to roster,2 bench(reject)');
            $table->boolean('is_active')->default(0)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_team_members');
    }
};
