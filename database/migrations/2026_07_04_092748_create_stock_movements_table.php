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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('epp_variant_id')->constrained('epp_variants')->cascadeOnDelete();
            $table->foreignId('delivery_detail_id')->nullable()->constrained('delivery_details')->nullOnDelete();
            $table->integer('quantity');
            $table->string('type'); // input, dispatch, adjustment, etc.
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
