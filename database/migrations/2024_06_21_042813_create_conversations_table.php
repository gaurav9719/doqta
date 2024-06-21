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
        Schema::create('conversations', function (Blueprint $table) {

            $table->id()->index();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable()->index();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_active')->default(1);
            $table->boolean('is_group')->default(false);
            $table->unsignedBigInteger('message_id')->index();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
