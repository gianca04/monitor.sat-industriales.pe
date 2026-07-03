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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->string('request_number')->unique()->nullable();
            $table->text('description');
            $table->foreignId('sub_client_id')->nullable()->constrained('sub_clients')->onDelete('cascade');
            $table->foreignId('cotizador_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->date('visit_date')->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->date('submission_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->enum('status', ['pending', 'attended', 'rejected'])->default('pending');
            $table->string('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
