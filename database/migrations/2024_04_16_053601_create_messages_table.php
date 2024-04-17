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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->foreign('inbox_id')->references('id')->on('inboxes')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->text('message')->nullable();
            $table->text('media')->nullable();
            $table->string('media_thumbnail')->nullable();
            $table->string('lat')->nullable()->comment('message type 4 save latitude');
            $table->string('long')->nullable()->comment('message type 4 save lat longitude');
            $table->string('address')->nullable()->comment('message type 4 save lat address');
            $table->integer('message_type')->default(1)->comment('1:text,2:audio,3:video,4:location,5:contact_share,6:document_share');
            $table->bigInteger('replied_to_message_id')->default(0)->comment('it show the reply of message id');
            $table->tinyInteger('is_user1_trash')->default(0)->comment('0:not,1:delete');
            $table->tinyInteger('is_user2_trash')->default(0)->comment('0:not,1:delete');
            $table->tinyInteger('isread')->default(0)->comment('0:unread,1:read');
            $table->dateTime('message_read_time')->nullable();
            $table->tinyInteger('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
