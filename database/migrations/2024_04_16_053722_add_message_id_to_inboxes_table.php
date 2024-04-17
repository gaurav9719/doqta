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
            $table->unsignedBigInteger('message_id')->nullable()->index()->after('id');
            $table->foreign('message_id')->references('id')->on('messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inboxes', function (Blueprint $table) {
            //
            $table->dropColumn('message_id');
        });
    }
};
