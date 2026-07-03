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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('requests')->onDelete('cascade');
            $table->string('work_order_number')->nullable();
            $table->enum('task', ['OPEX', 'CAPEX'])->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('fracttal_status');
            $table->date('review_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->integer('purchase_order')->nullable();
            $table->integer('migo')->nullable();
            $table->enum('work_order_status', ['Quoted', 'Approved', 'In Progress', 'Completed', 'Invoiced']);
            $table->string('work_order_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};