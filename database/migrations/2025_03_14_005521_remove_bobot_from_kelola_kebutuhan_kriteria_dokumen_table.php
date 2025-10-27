<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveBobotFromKelolaKebutuhanKriteriaDokumenTable extends Migration
{
    public function up()
    {
        Schema::table('kelola_kebutuhan_kriteria_dokumen', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
    }

    public function down()
    {
        Schema::table('kelola_kebutuhan_kriteria_dokumen', function (Blueprint $table) {
            $table->double('bobot')->default(0)->after('keterangan');
        });
    }
}