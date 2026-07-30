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
        Schema::table('epp_variants', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->default(0.00)->after('variant_name');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity');
        });

        Schema::table('delivery_details', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epp_variants', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('delivery_details', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
