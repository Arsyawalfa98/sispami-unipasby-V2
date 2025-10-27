<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToPemenuhanDokumenTable extends Migration
{
    public function up()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('fakultas');
            // Bisa pakai enum jika status sudah fixed
            // $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak'])->default('draft')->after('fakultas');
        });
    }

    public function down()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}