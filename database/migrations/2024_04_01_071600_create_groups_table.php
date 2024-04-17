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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('cover_photo')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('visibility')->default(true)->comment('1:public,0:not'); // Indicates if the group is public or private
            $table->boolean('approval_required')->default(false); // Indicates if membership approval is required
            $table->integer('member_count')->default(0);
            $table->integer('post_count')->default(0);
            $table->boolean('is_active')->default(1)->comment('1:active,0:not active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
