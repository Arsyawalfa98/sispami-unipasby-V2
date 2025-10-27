<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraintFromKriteriaDokumen extends Migration
{
    public function up()
    {
        Schema::table('kriteria_dokumen', function (Blueprint $table) {
            // Hapus unique constraint
            $table->dropUnique('kriteria_dokumen_unique');
        });
    }

    public function down()
    {
        Schema::table('kriteria_dokumen', function (Blueprint $table) {
            // Tambahkan kembali unique constraint jika perlu rollback
            $table->unique(['lembaga_akreditasi_id', 'jenjang_id', 'judul_kriteria_dokumen_id'], 'kriteria_dokumen_unique');
        });
    }
}