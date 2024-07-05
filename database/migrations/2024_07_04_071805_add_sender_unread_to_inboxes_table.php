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
        Schema::table('inboxes', function (Blueprint $table) {
            //

            $table->unsignedBigInteger('user1_unread')->nullable()->after('is_user2_trash');
            $table->unsignedBigInteger('user2_unread')->nullable()->after('user1_unread');
            $table->foreign('user1_unread')->references('id')->on('users');
            $table->foreign('user2_unread')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inboxes', function (Blueprint $table) {
            //

        $table->dropColumn('user1_unread');
        $table->dropColumn('user2_unread');




        });
    }
};
