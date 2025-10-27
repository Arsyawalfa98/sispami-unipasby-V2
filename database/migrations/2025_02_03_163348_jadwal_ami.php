<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jadwal_ami', function (Blueprint $table) {
            $table->id();
            $table->string('prodi');
            $table->string('fakultas');
            $table->string('standar_akreditasi');
            $table->string('periode');
            $table->datetime('tanggal_mulai');
            $table->datetime('tanggal_selesai');
            $table->timestamps();
        });

        Schema::create('jadwal_ami_auditor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_ami_id')->constrained('jadwal_ami')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jadwal_ami_auditor');
        Schema::dropIfExists('jadwal_ami');
    }
};