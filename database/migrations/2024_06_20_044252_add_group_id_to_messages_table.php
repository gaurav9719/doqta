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
        Schema::table('messages', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('group_id')->nullable()->index()->after('inbox_id');
            $table->foreign('group_id')->references('id')->on('chat_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            //
            $table->dropPrimary('group_id');
            $table->dropColumn('group_id');
        });
    }
};
