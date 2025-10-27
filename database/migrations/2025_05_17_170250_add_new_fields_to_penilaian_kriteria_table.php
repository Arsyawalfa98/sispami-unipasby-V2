<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToPenilaianKriteriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penilaian_kriteria', function (Blueprint $table) {
            $table->datetime('tanggal_pemenuhan')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->string('status_temuan')->nullable();
            $table->text('hasil_ami')->nullable();
            $table->text('output')->nullable();
            $table->text('akar_penyebab_masalah')->nullable();
            $table->text('tinjauan_efektivitas_koreksi')->nullable();
            $table->text('kesimpulan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penilaian_kriteria', function (Blueprint $table) {
            $table->dropColumn('tanggal_pemenuhan');
            $table->dropColumn('penanggung_jawab');
            $table->dropColumn('status_temuan');
            $table->dropColumn('hasil_ami');
            $table->dropColumn('output');
            $table->dropColumn('akar_penyebab_masalah');
            $table->dropColumn('tinjauan_efektivitas_koreksi');
            $table->dropColumn('kesimpulan');
        });
    }
}