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
        Schema::create('product_ref_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->index()->nullable();
            $table->unsignedInteger('supplier_id')->index()->nullable();
            $table->unsignedInteger('stock')->index()->default(0);
            $table->string('name')->index()->nullable();
            $table->float('supplier_get_price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ref_suppliers');
    }
};
