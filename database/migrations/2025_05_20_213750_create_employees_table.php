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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['DNI','PASAPORTE', 'CARNET DE EXTRANJERIA'])->default('DNI');
            $table->string('document_number',12);
            $table->string('first_name',40);
            $table->string('last_name',40);
            $table->string('address',40);
            $table->date('date_contract');
            $table->date('date_birth');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};