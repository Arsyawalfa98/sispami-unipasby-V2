<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kategori_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->timestamps();
        });
        
        Schema::create('kategori_dokumen_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_dokumen_id')
                  ->constrained('kategori_dokumen')  // Sesuaikan nama tabel
                  ->onDelete('cascade');
            $table->foreignId('role_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('kategori_dokumen_role');
        Schema::dropIfExists('kategori_dokumen');
    }
};
