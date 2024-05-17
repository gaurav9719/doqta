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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiver_id')->comment("userid to whom notificaions will be shown")->nullable()->index();
            $table->unsignedBigInteger('sender_id')->comment("notification send by")->nullable();
            $table->tinyInteger('notification_type')->comment('1=document verified, 2=document rejected, 3=Profile not complete, 4=Plan activated, 5=Plan expired, 6=Password changed, 7=Started supporting / Accepted support request, 8=Requested to support, 9=Joined the community, 10=Posted in community, 11=Post like, 12=Comment , 13=Comment like, 14=Comment reply, 15=Reposted the post , 16=Post share, 17=Support new member')->nullable();
            $table->boolean('is_read')->default(0)->comment('0:no,1:read');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('message')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0:inactive,1:active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
