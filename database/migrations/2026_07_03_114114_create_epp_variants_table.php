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
        Schema::create('epp_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epp_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique()->nullable();
            $table->string('size', 20)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('presentation', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epp_variants');
    }
};
