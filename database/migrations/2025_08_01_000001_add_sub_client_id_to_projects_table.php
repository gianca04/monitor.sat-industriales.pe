<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_client_id')->nullable()->after('quote_id');
            $table->foreign('sub_client_id')->references('id')->on('sub_clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['sub_client_id']);
            $table->dropColumn('sub_client_id');
        });
    }
};
