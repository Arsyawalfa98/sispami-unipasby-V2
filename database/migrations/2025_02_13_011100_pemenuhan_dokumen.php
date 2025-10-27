<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pemenuhan_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kriteria_dokumen_id')->constrained('kriteria_dokumen')->onDelete('cascade');
            $table->string('kriteria');
            $table->string('element');
            $table->string('indikator');
            $table->string('nama_dokumen');
            $table->string('tipe_dokumen');
            $table->text('keterangan')->nullable();
            $table->string('file')->nullable();
            $table->string('periode');
            $table->text('tambahan_informasi')->nullable();
            $table->string('prodi');
            $table->string('fakultas');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pemenuhan_dokumen');
    }
};