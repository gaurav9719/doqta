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
            //
            $table->boolean('is_public')->default(true)->after('remember_token')->comment('1:public,0:private');
            $table->integer('followers_count')->default(0)->after('is_public')->comment('count the user who followinf me');
            $table->integer('followings_count')->default(0)->after('followers_count')->comment('whose i am follwing');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('is_public');

        });
    }
};
