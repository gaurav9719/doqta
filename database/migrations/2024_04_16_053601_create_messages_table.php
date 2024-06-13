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
            $table->unsignedBigInteger('inbox_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message')->nullable();
            $table->text('media')->nullable();
            $table->string('media_thumbnail')->nullable();
            $table->string('lat')->nullable()->comment('message type 4 save latitude');
            $table->string('long')->nullable()->comment('message type 4 save lat longitude');
            $table->string('address')->nullable()->comment('message type 4 save lat address');
            $table->integer('message_type')->default(1)->comment('1:text,2image: 3audio,4:video,5:location,6:contact_share,7:document_share');
            $table->bigInteger('replied_to_message_id')->default(0)->comment('it show the reply of message id');
            $table->tinyInteger('isread')->default(0)->comment('0:unread,1:read');
            $table->dateTime('message_read_time')->nullable();
            $table->unsignedBigInteger('is_user1_trash')->nullable();
            $table->unsignedBigInteger('is_user2_trash')->nullable();
            $table->tinyInteger('is_active')->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
            
            $table->foreign('inbox_id')->references('id')->on('inboxes')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('is_user1_trash')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('is_user2_trash')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['inbox_id', 'sender_id', 'is_active', 'is_user1_trash', 'is_user2_trash'], 'msg_inbox_sender_active_trash_idx');
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
