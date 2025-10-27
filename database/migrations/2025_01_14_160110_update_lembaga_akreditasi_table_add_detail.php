<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Backup data lama
        $oldData = DB::table('lembaga_akreditasi')->get();
        
        // Hapus kolom yang tidak digunakan
        Schema::table('lembaga_akreditasi', function (Blueprint $table) {
            $table->dropColumn(['prodi', 'fakultas']);
        });

        // Buat tabel detail untuk prodi dan fakultas
        Schema::create('lembaga_akreditasi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lembaga_akreditasi_id')
                  ->constrained('lembaga_akreditasi')
                  ->onDelete('cascade');
            $table->string('prodi');
            $table->string('fakultas');
            $table->timestamps();
        });

        // Restore data lama ke struktur baru
        foreach($oldData as $data) {
            DB::table('lembaga_akreditasi_detail')->insert([
                'lembaga_akreditasi_id' => $data->id,
                'prodi' => $data->prodi,
                'fakultas' => $data->fakultas,
                'created_at' => now(),
                'updated_at' => now()  
            ]);
        }
    }

    public function down()
    {
        // Ambil data dari tabel detail
        $details = DB::table('lembaga_akreditasi_detail')->get();

        // Tambah kembali kolom yang dihapus
        Schema::table('lembaga_akreditasi', function (Blueprint $table) {
            $table->string('prodi');
            $table->string('fakultas');
        });

        // Restore data ke format lama
        foreach($details as $detail) {
            DB::table('lembaga_akreditasi')
                ->where('id', $detail->lembaga_akreditasi_id)
                ->update([
                    'prodi' => $detail->prodi,
                    'fakultas' => $detail->fakultas
                ]);
        }

        Schema::dropIfExists('lembaga_akreditasi_detail');
    }
};