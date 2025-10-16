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
        Schema::create('product_landings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->index();
            $table->string('signature')->index()->nullable();
            $table->string('title')->nullable();
            $table->tinyInteger('active')->index()->nullable();
            $table->string('type')->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->string('operator_type')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_landings');
    }
};
