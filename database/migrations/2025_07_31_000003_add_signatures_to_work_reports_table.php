<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->longText('supervisor_signature')->nullable()->after('description');
            $table->longText('manager_signature')->nullable()->after('supervisor_signature');
        });
    }

    public function down()
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropColumn(['supervisor_signature', 'manager_signature']);
        });
    }
};
