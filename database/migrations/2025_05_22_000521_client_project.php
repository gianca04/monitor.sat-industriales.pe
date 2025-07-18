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
        Schema::create('client_project', function (Blueprint $table) {
            $table->id();

            // Foreign key to clients table
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Foreign key to projects table
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            // To avoid duplicates
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_project');
    }
};
