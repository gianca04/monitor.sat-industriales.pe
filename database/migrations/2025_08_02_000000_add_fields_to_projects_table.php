<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->longText('tools')->nullable();
            $table->longText('personnel')->nullable();
            $table->longText('materials')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropColumn(['tools', 'personnel', 'materials']);
        });
    }
}
