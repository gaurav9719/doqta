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
        Schema::table('activity_logs', function (Blueprint $table) {

            $table->unsignedBigInteger('like_id')->nullable()->after('community_id');
            $table->unsignedBigInteger('support_user_id')->nullable()->after('like_id');
            $table->unsignedBigInteger('community_member_id')->nullable()->after('support_user_id');
            $table->bigInteger('parent_id')->nullable()->after('community_member_id');
            $table->foreign('like_id')->references('id')->on('post_likes')->cascadeOnDelete();
            $table->foreign('support_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('community_member_id')->references('id')->on('group_members')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            //
            $table->dropForeign(['like_id']);
            $table->dropColumn('like_id');
            $table->dropForeign(['support_user_id']);
            $table->dropColumn('support_user_id');
            $table->dropForeign(['community_member_id']);
            $table->dropColumn('community_member_id');
        });
    }
};
