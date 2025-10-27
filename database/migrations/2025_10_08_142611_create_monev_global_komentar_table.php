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
        Schema::create('monev_global_komentar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lembaga_akreditasi_id');
            $table->unsignedBigInteger('jenjang_id');
            $table->string('prodi', 255);
            $table->string('periode_atau_tahun', 50);
            $table->enum('status_temuan', ['KETIDAKSESUAIAN', 'TERCAPAI'])->default('KETIDAKSESUAIAN');
            $table->text('komentar_global');
            $table->unsignedBigInteger('admin_id');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('lembaga_akreditasi_id')
                  ->references('id')
                  ->on('lembaga_akreditasi')
                  ->onDelete('cascade');
            
            $table->foreign('jenjang_id')
                  ->references('id')
                  ->on('jenjang')
                  ->onDelete('cascade');
            
            $table->foreign('admin_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Composite Index untuk query group
            $table->index(
                ['lembaga_akreditasi_id', 'jenjang_id', 'prodi', 'periode_atau_tahun', 'status_temuan'], 
                'idx_group_status'
            );
            
            // Additional indexes
            $table->index('admin_id', 'idx_admin');
            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monev_global_komentar');
    }
};