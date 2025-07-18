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
        Schema::table('clients', function (Blueprint $table) {
            // Si la columna 'ruc' ya no es necesaria, debes eliminarla antes con una migración aparte,
            // o hacerlo manualmente, no en la misma migración que añade otras columnas.

            $table->string('document_type', 20)
                ->after('id')
                ->comment('RUC, DNI, FOREIGN_CARD, PASSPORT');

            $table->string('document_number', 11)
                ->after('document_type')
                ->comment('Document number');

            $table->string('person_type', 20)
                ->after('document_number')
                ->comment('Natural Person, Legal Entity');

            $table->string('address')
                ->nullable()
                ->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['document_type', 'document_number', 'person_type', 'address']);

            // La columna 'ruc' no se agrega aquí en down() si fue eliminada en otra migración,
            // para evitar conflictos o inconsistencias.
        });
    }
};
