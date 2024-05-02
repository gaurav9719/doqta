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
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->unsignedBigInteger('user_plan_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->float('amount')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('is_trial_plan')->default(0);
            $table->boolean('purchased_device')->default(0);
            $table->tinyInteger('payment_by')->nullable()->comment('1=stripe, 2=paypal, 3=google_pay');
            $table->tinyInteger('status')->nullable()->comment('1=pending, 2=completed, 3=canceled, 4=failed, 5=refunded');
            $table->string('failed_reason')->nullable();
            $table->foreign('user_plan_id')->references('id')->on('user_plans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_active')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
