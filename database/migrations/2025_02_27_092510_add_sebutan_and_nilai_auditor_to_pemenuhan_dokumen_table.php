<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSebutanAndNilaiAuditorToPemenuhanDokumenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->string('sebutan')->nullable()->after('nilai');
            $table->double('nilai_auditor')->nullable()->after('tertimbang');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->dropColumn('sebutan');
            $table->dropColumn('nilai_auditor');
        });
    }
}