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
        Schema::table('notifications', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('like_id')->after('message')->nullable();
            $table->unsignedBigInteger('community_member_id')->after('like_id')->nullable();
            $table->unsignedBigInteger('user_plan_id')->after('community_member_id')->nullable();
            $table->unsignedBigInteger('comment_like_id')->after('user_plan_id')->nullable();
            $table->foreign('like_id')->references('id')->on('post_likes')->cascadeOnDelete();
            $table->foreign('community_member_id')->references('id')->on('group_members')->cascadeOnDelete();
            $table->foreign('user_plan_id')->references('id')->on('user_plans')->cascadeOnDelete();
            $table->foreign('comment_like_id')->references('id')->on('comment_likes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            //
            // $table->dropColumn('like_id');
            // $table->dropColumn('community_member_id');
            // $table->dropColumn('user_plan_id');
            // $table->dropColumn('comment_like_id');
        });
    }
};
