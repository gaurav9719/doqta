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
        Schema::create('user_plans', function (Blueprint $table) {
         
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->string('original_transaction_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->dateTime('last_update')->nullable();
            $table->boolean('is_trial_plan')->default(0);
            $table->tinyInteger('purchased_device')->default(1)->comment('1:ios,2:andriod');
            $table->dateTime('cancelled_period_end')->nullable();
            $table->dateTime('cancelled_period_at_end')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->integer('payment_status')->comment('0=not, 1=done')->nullable();
            $table->tinyInteger('is_active')->default(0)->comment('0:inactive,1:active,2:cancelled,3:expired')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plans');
    }
};
