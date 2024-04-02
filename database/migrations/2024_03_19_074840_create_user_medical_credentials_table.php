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
        Schema::create('user_medical_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('medicial_credential')->nullable();
            $table->unsignedBigInteger('specialty')->nullable()->index();
            $table->unsignedBigInteger('medicial_degree_type')->nullable()->index();
            $table->foreign('medicial_degree_type')->references('id')->on('medical_credentials')->onDelete('set null');
            $table->foreign('specialty')->references('id')->on('specialties')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('medicial_document')->nullable();
            $table->boolean('verified_status')->default(false)->comment('1:verified,0:not')->index();
            $table->boolean('is_active')->default(0)->comment('0:inactive,1:active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_medical_credentials');
    }
};
