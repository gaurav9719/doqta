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
        Schema::create('feelings', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('feeling',150)->nullable();
            $table->string('selected',150)->nullable();
            $table->boolean('is_active')->default(1)->comment('1:active,0:active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feelings');
    }
};
