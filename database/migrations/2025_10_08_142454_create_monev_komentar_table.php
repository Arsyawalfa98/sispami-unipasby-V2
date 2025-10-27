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
        Schema::create('monev_komentar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kriteria_dokumen_id');
            $table->string('prodi', 255);
            $table->enum('status_temuan', ['KETIDAKSESUAIAN', 'TERCAPAI'])->default('KETIDAKSESUAIAN');
            $table->text('komentar_element')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('kriteria_dokumen_id')
                  ->references('id')
                  ->on('kriteria_dokumen')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Indexes untuk performa query
            $table->index(['kriteria_dokumen_id', 'prodi', 'status_temuan'], 'idx_kriteria_prodi_status');
            $table->index('user_id', 'idx_user');
            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monev_komentar');
    }
};