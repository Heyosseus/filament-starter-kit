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
        Schema::create('product_manufacturers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordering')->default(0);
            $table->string('name')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('slug')->index()->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('pin')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_manufacturers');
    }
};
