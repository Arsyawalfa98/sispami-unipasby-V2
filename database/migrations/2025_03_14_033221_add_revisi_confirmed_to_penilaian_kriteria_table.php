<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisiConfirmedToPenilaianKriteriaTable extends Migration
{
    public function up()
    {
        Schema::table('penilaian_kriteria', function (Blueprint $table) {
            $table->boolean('revisi_confirmed')->default(false)->after('revisi');
        });
    }

    public function down()
    {
        Schema::table('penilaian_kriteria', function (Blueprint $table) {
            $table->dropColumn('revisi_confirmed');
        });
    }
}