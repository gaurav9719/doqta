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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('pronoun')->nullable()->after('gender')->index();
            $table->unsignedBigInteger('ethnicity')->nullable()->after('pronoun')->index();
            $table->foreign('pronoun')->references('id')->on('pronouns')->onDelete('set null');
            $table->foreign('ethnicity')->references('id')->on('ethnicities')->onDelete('set null');
            $table->boolean('guideliness')->default(0)->comment('0:not aggree,1:agree')->after('ethnicity');
            $table->tinyInteger('complete_step')->default(0)->after('guideliness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
