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
        Schema::table('comments', function (Blueprint $table) {
            //
            $table->boolean('is_high_confidence')->default(0)->comment('0:no,1:yes')->after('comment_type');
            $table->double('ai_score')->after('is_high_confidence')->default(0);
            $table->index(['is_high_confidence']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            //
            $table->dropColumn('is_high_confidence');
            $table->dropColumn('ai_score');
        });
    }
};
