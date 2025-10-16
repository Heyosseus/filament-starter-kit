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
        Schema::create('product_fake_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('photo')->nullable();
            $table->string('message')->nullable();
            $table->string('user_name')->nullable();
            $table->unsignedInteger('gender')->default(0);
            $table->unsignedInteger('product_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_fake_reviews');
    }
};
