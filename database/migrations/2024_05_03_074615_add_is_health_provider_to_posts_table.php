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
            //
            $table->boolean('is_health_provider')->after('wrote_by')->default(0)->comment('1:yes,0:not')->index();
            $table->integer('support_count')->after('is_health_provider')->default(0)->index();
            $table->integer('helpful_count')->after('support_count')->default(0)->index();
            $table->integer('unhelpful_count')->after('helpful_count')->default(0)->index();
            $table->integer('total_count')->after('unhelpful_count')->default(0)->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            //
            $table->dropColumn('is_health_provider');
            $table->dropColumn('support_count');
            $table->dropColumn('helpful_count');
            $table->dropColumn('unhelpful_count');
            $table->dropColumn('total_count');
        });
    }
};
