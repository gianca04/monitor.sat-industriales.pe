<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('epp_photos');

        Schema::table('epps', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            $table->dropColumn('photos');
        });

        Schema::create('epp_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epp_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path', 255);
            $table->timestamps();
        });
    }
};
