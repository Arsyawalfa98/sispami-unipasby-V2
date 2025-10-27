<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisiToPemenuhanDokumenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->text('revisi')->nullable()->after('tertimbang');
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
            $table->dropColumn('revisi');
        });
    }
}