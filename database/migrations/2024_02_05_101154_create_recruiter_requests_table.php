<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recruiter_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('dater_id');
            $table->unsignedBigInteger('recruiter_id')->nullable()->comment('recruiter_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('recruiter_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('request_status')->default(0)->comment('0:pending,1:accept,2:reject');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->date('request_on')->default(Carbon::now())->comment('current_utc_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruiter_requests');
    }
};
