<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBobotToKelolaKebutuhanKriteriaDokumenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kelola_kebutuhan_kriteria_dokumen', function (Blueprint $table) {
            $table->double('bobot')->default(0)->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kelola_kebutuhan_kriteria_dokumen', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
    }
}