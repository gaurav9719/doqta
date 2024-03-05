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
        Schema::table('point_histories', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('match_user_id')->nullable()->after('reference_user_id')->index();
            $table->foreign('match_user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('set null');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_histories', function (Blueprint $table) {
            //
            $table->dropColumn('match_user_id');
        });
    }
};
