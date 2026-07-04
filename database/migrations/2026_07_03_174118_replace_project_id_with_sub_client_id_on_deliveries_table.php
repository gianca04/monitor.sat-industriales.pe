<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public static string $migrationName = 'replace_project_id_with_sub_client_id_on_deliveries_table';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->foreignId('sub_client_id')->nullable()->constrained('sub_clients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['sub_client_id']);
            $table->dropColumn('sub_client_id');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
        });
    }
};
