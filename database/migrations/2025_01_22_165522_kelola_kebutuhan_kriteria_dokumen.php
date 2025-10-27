<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kelola_kebutuhan_kriteria_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kriteria_dokumen_id')->constrained('kriteria_dokumen')->onDelete('cascade');
            $table->string('nama_dokumen');
            $table->string('tipe_dokumen');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kelola_kebutuhan_kriteria_dokumen');
    }
};