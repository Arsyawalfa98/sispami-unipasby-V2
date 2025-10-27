<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_prodi');
            $table->enum('jenjang', ['S1', 'S2', 'S3', 'D4', 'D3','PPG']);
            $table->string('jurusan');
            $table->string('fakultas');
            $table->string('status_akreditasi')->nullable();
            $table->date('tanggal_kadarluarsa')->nullable();
            $table->string('bukti')->nullable(); // Untuk menyimpan path file
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
