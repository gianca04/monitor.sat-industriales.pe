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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger(column: 'client_id');  // Añadir la columna 'client_id'
            $table->foreign('client_id')->references('id')->on(table: 'clients')->onDelete('cascade');  // Definir la clave foránea
            // Definir las claves foráneas
            $table->unsignedBigInteger('employee_id');  // 'id_empleado'
            $table->unsignedBigInteger('sub_client_id');  // Cambio aquí a singular

            $table->string('TDR');

            $table->string('quote_file')->nullable();  // 'archivo_cotizacion' (opcional)

            $table->string('correlative')->unique();  // 'correlativo'
            $table->string('contractor');  // 'contratista'
            $table->enum('pe_pt', ['PT', 'PE', 'PE_PT']);  // Enum for 'pe_pt'
            $table->string('project_description');  // 'descripcion_proyecto'
            $table->string('location');  // 'lugar'
            $table->date('delivery_term');  // 'plazo_entrega'

            $table->enum('status', [
                'unassigned',     // No tiene ningún empleado asignado
                'in_progress',    // Recibida y en proceso
                'under_review',   // En espera de revisión por el gerente
                'sent',           // Enviada al cliente
                'rejected',       // Rechazada por el cliente
                'accepted'        // Aceptada por el cliente
            ]);

            $table->timestamps();  // Created_at and updated_at

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('sub_client_id')->references('id')->on('sub_clients')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
