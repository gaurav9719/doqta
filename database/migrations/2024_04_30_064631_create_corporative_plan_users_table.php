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
        Schema::create('corporative_plan_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_plan_id')->nullable()->index();
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('corporate_email')->nullable();
            $table->string('otp')->nullable();
            $table->string('otp_expiry')->nullable();
            $table->string('is_verified')->nullable()->comment('1=verified, 0=not');
            $table->tinyInteger('is_active')->default(0)->comment('1=active, 0=inactive');
            $table->foreign('user_plan_id')->references('id')->on('user_plans')->onDelete('cascade');
            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporative_plan_users');
    }
};
