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
        Schema::table('posts', function (Blueprint $table) {

            $table->string('link')->after('media_url')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->foreign('parent_id')->references('id')->on('posts')->onDelete('cascade');
            #----- OLD --------#
            // $table->bigInteger('like_count')->after('group_id')->default(0);
            // $table->bigInteger('comment_count')->after('like_count')->default(0);
            #----- OLD --------#

            #---------- NEW ---------#
            $table->bigInteger('total_likes_count')->after('group_id')->default(0);
            $table->bigInteger('total_comment_count')->after('total_likes_count')->default(0);
            #---------- NEW ---------#
            $table->bigInteger('repost_count')->after('total_likes_count')->default(0);
            $table->bigInteger('share_count')->after('repost_count')->default(0);
            $table->boolean('is_high_confidence')->after('share_count')->default(0);
            $table->tinyInteger('is_active')->after('is_high_confidence')->default(1)->comment('1:active,0:inactive,2:hide');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
        
            $table->dropColumn('link');
            $table->dropColumn('total_likes_count');
            $table->dropColumn('total_comment_count');
            $table->dropColumn('repost_count');
            $table->dropColumn('share_count');
            $table->dropColumn('is_high_confidence');
            $table->dropColumn('is_active');
        
        });
    }
};
