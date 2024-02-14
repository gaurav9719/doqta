<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\PointSystemSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_systems', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('user_role')->default(2)->comment('1:admin,2:dater,3:recruiter');
            $table->string('activity_name')->nullable();
            $table->integer('point')->default(1);
            $table->string('slug')->nullable();
            $table->string('keyword')->nullable();
            $table->boolean('is_active')->default(1)->comment('1');
            $table->timestamps();
        });

        Artisan::call('db:seed', ['--class' => PointSystemSeeder::class]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_systems');
    }
};
