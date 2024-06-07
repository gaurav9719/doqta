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
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('original_transaction_id')->nullable();
            $table->float('amount')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->dateTime('last_update')->nullable();
            $table->boolean('is_trial_plan')->default(0);
            $table->boolean('purchased_device')->default(0)->comment('1=IOS, 2=Android');
            $table->tinyInteger('status')->nullable()->comment('1=pending, 2=completed, 3=canceled, 4=failed, 5=refunded');
            $table->tinyInteger('payment_by')->nullable()->comment('1=app_in_purchage, 2=stripe, 3=paypal, 4=google_pay');
            $table->dateTime('cancelled_at')->nullable();
            // $table->string('payment_method')->nullable()->comment('1:Cards, 2:Bank transfer, 3:Wallets');
            $table->string('reason')->nullable();
            $table->foreign('user_plan_id')->references('id')->on('user_plans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->boolean('is_active')->default(0)->comment('0:inactive,1:active,2:cancelled,3:expired')->index();
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
