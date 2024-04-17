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
        Schema::create('activity_logs', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->unsignedBigInteger('community_id')->nullable()->index();
            $table->tinyInteger('action')->comment('1:post,2:like,3:comment')->default(0)->index(); // e.g., 'post', 'reaction', 'comment'
            $table->text('action_details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
            $table->string('location')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->boolean('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
