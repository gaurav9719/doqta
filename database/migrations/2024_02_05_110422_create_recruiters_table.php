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
        Schema::create('recruiters', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("dater_id")->nullable();
            $table->unsignedBigInteger("recruiter_id")->nullable();
            $table->tinyInteger("recruiter_type")->default(1)->comment("1:invited friend coach,2 ghost coach");
            $table->foreign('dater_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('recruiter_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('status')->default(1)->comment('0:inactive,1:active');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruiters');
    }
};
