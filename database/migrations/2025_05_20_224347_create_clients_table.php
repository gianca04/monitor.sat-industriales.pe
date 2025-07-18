<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 11)->unique()->comment('Unique 11-digit RUC number');
            $table->string('business_name')->comment('Client business name');
            $table->text('description')->nullable()->comment('Description of the client');
            $table->string('contact_phone')->nullable()->comment('Contact phone number');
            $table->string('contact_email')->nullable()->comment('Contact email address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
