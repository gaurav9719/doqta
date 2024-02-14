<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->comment("userid who made reference")->nullable();
            $table->unsignedBigInteger('referred_id')->comment("")->nullable();
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->date('refered_on')->default(Carbon::now());
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
