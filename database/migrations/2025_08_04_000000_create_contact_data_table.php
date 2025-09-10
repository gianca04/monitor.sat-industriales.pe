<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_client_id');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('contact_name')->nullable();
            $table->timestamps();

            $table->foreign('sub_client_id')->references('id')->on('sub_clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_data');
    }
};
