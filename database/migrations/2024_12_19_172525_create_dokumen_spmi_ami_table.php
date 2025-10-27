<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dokumen_spmi_ami', function (Blueprint $table) {
            $table->id();
            $table->string('kategori_dokumen');  // Untuk KKA, PKA
            $table->string('nama_dokumen');      // Form 4 KKA, Form 5 KKA, Form 2 PKA
            $table->string('file_path');         // Path file yang diupload
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dokumen_spmi_ami');
    }
};