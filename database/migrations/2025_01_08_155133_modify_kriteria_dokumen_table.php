<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('kriteria_dokumen')) {
            Schema::drop('kriteria_dokumen');
        }

        Schema::create('kriteria_dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lembaga_akreditasi_id');
            $table->unsignedInteger('jenjang_id');
            $table->unsignedInteger('judul_kriteria_dokumen_id')->nullable();
            $table->string('kode')->nullable();
            $table->text('element')->nullable();
            $table->text('indikator')->nullable();
            $table->text('informasi')->nullable();
            $table->integer('kebutuhan_dokumen')->nullable();
            $table->timestamps();

            $table->unique(
                ['lembaga_akreditasi_id', 'jenjang_id', 'judul_kriteria_dokumen_id'],
                'kriteria_dokumen_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kriteria_dokumen', function (Blueprint $table) {
            //
        });
    }
};
