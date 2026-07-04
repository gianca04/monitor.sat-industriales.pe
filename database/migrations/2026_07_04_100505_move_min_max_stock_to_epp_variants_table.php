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
            $table->integer('minimum_stock')->default(0)->after('variant_name');
            $table->integer('maximum_stock')->default(0)->after('minimum_stock');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['minimum_stock', 'maximum_stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->integer('minimum_stock')->default(0)->after('current_stock');
            $table->integer('maximum_stock')->default(0)->after('minimum_stock');
        });

        Schema::table('epp_variants', function (Blueprint $table) {
            $table->dropColumn(['minimum_stock', 'maximum_stock']);
        });
    }
};
