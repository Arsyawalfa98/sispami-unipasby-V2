<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenilaianKriteriaTable extends Migration
{
    public function up()
    {
        Schema::create('penilaian_kriteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kriteria_dokumen_id');
            $table->string('prodi');
            $table->string('fakultas')->nullable();
            $table->string('periode_atau_tahun');
            $table->string('status')->default('draft');
            $table->double('nilai')->nullable();
            $table->string('sebutan')->nullable();
            $table->double('bobot')->nullable();
            $table->double('tertimbang')->nullable();
            $table->double('nilai_auditor')->nullable();
            $table->text('revisi')->nullable();
            $table->timestamps();
            
            $table->foreign('kriteria_dokumen_id')->references('id')->on('kriteria_dokumen')->onDelete('cascade');
            $table->unique(['kriteria_dokumen_id', 'prodi']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('penilaian_kriteria');
    }
}