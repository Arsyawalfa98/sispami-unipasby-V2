<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kriteria_dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lembaga_akreditasi_id');
            $table->unsignedInteger('jenjang_id');
            $table->unsignedInteger('judul_kriteria_dokumen_id');
            $table->string('periode_atau_tahun');
            $table->string('kode');
            $table->text('element');
            $table->text('indikator');
            $table->text('informasi');
            $table->integer('kebutuhan_dokumen');
            $table->timestamps();

            // Unique constraint untuk mencegah duplikasi data
            $table->unique(
                ['lembaga_akreditasi_id', 'jenjang_id', 'judul_kriteria_dokumen_id'],
                'kriteria_dokumen_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('kriteria_dokumen');
    }
};