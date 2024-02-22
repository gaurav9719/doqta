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
        Schema::table('partner_matches', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('send_by')->nullable()->after('user2_id');
            $table->foreign('send_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('message')->nullable()->after('send_by');
            $table->string('media')->nullable()->after('message');
            $table->string('media_thumbnail')->nullable()->after('media');
            $table->integer('message_type')->default(1)->comment('1:text,2:audio,3:video,4:location,5:contact_share,6:document_share')->after('media_thumbnail');
            $table->boolean('is_sender_trash')->default(0)->comment('0:not,1:delete')->after('message_type');
            $table->boolean('is_reciver_trash')->default(0)->comment('0:not,1:delete')->after('is_sender_trash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_matches', function (Blueprint $table) {
            //
        });
    }
};
