<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBobotToPemenuhanDokumenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->double('bobot')->default(0)->after('nilai');
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
            $table->dropColumn('bobot');
        });
    }
}