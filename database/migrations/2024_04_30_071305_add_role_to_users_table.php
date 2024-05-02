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
            
            $table->tinyInteger('role')->comment('1=user, 3=admin')->default(1)->after('is_public')->index();
            $table->unsignedBigInteger('user_plan_id')->nullable()->after('is_public');
            $table->boolean('is_trial_used')->default(0)->comment('1=trial used, 0=not used')->after('is_public');
            $table->tinyInteger('plan_status')->default(0)->comment('1=active, 0=not active, 2=expired')->after('is_public');
            $table->foreign('user_plan_id')->references('id')->on('user_plans')->onDelete('cascade');
            $table->string('stripe_customer_id')->nullable()->after('bio');
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
        });
    }
};
