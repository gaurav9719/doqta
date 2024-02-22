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
        Schema::table('my_rosters', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('match_id')->after('my_team_member_id')->nullable();
            $table->foreign('match_id')->references('id')->on('my_rosters')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('my_rosters', function (Blueprint $table) {
            //
            $table->dropColumn('match_id');
            
        });
    }
};
