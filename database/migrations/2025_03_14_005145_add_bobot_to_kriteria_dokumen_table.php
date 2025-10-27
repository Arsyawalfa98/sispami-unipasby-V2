<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBobotToKriteriaDokumenTable extends Migration
{
    public function up()
    {
        Schema::table('kriteria_dokumen', function (Blueprint $table) {
            $table->double('bobot')->default(0)->after('kebutuhan_dokumen');
        });
    }

    public function down()
    {
        Schema::table('kriteria_dokumen', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
    }
}