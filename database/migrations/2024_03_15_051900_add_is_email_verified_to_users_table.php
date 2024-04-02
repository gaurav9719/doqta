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

            $table->boolean('is_email_verified')->default(0)->comment('0:not,1:yes')->after('otp_expiry_time');
            $table->tinyInteger('signup_process')->default(0)->comment('0:not,1:yes')->after('is_email_verified');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('otp_expiry_time');
        });
    }
};
