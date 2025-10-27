<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            // Ubah kolom dari string (varchar 255) ke text untuk menampung data lebih panjang
            $table->text('kriteria')->change();
            $table->text('element')->change();
            $table->text('indikator')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            // Kembalikan ke tipe string jika rollback
            $table->string('kriteria')->change();
            $table->string('element')->change();
            $table->string('indikator')->change();
        });
    }
};
