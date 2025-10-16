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
        Schema::create('product_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->index()->nullable();
            $table->tinyInteger('cash')->index()->nullable();
            $table->tinyInteger('installment')->nullable();
            $table->tinyInteger('bank')->nullable();
            $table->tinyInteger('voucher')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_payment_methods');
    }
};
