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
            
            $table->unsignedBigInteger('user_plan_id')->nullable()->after('is_public');
            $table->boolean('is_trial_used')->default(0)->comment('1=trial used, 0=not used')->after('is_public');
            $table->tinyInteger('plan_status')->default(0)->comment('1=active, 0=not active, 2=expired')->after('is_public');
            $table->foreign('user_plan_id')->references('id')->on('user_plans')->onDelete('cascade');
            $table->string('stripe_customer_id')->nullable()->after('bio');
            $table->boolean('is_document_verified')->after('is_email_verified')->default(0)->comment('1:verified,0:not');
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropForeign(['user_plan_id']);
            $table->dropColumn('is_trial_used');
            $table->dropColumn('plan_status');
            $table->dropColumn('user_plan_id');
            $table->dropColumn('stripe_customer_id');
        });
    }
};
