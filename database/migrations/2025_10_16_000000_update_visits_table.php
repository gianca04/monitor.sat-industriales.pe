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
        Schema::table('visits', function (Blueprint $table) {
            $table->string('employee_signature')->nullable();
            $table->string('manager_signature')->nullable();
            $table->text('suggestions')->nullable();
            $table->text('tools')->nullable();
            $table->text('materials')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('report_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn([
                'employee_signature',
                'manager_signature',
                'suggestions',
                'tools',
                'materials',
                'start_time',
                'end_time',
                'report_date',
            ]);
        });
    }
};