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
        Schema::table('jadwal_ami', function (Blueprint $table) {
            // Menambahkan kolom upload schedule setelah updated_at (kolom terakhir)
            $table->datetime('upload_mulai')->nullable()->after('updated_at');
            $table->datetime('upload_selesai')->nullable()->after('upload_mulai');
            $table->boolean('upload_enabled')->default(false)->after('upload_selesai');
            $table->text('upload_keterangan')->nullable()->after('upload_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_ami', function (Blueprint $table) {
            $table->dropColumn([
                'upload_mulai',
                'upload_selesai', 
                'upload_enabled',
                'upload_keterangan'
            ]);
        });
    }
};