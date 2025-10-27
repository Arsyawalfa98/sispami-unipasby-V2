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
        Schema::create('user_prodi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('kode_prodi', 20);
            $table->string('nama_prodi', 255)->nullable();
            $table->string('kode_fakultas', 20)->nullable();
            $table->string('nama_fakultas', 255)->nullable();
            $table->boolean('is_default')->default(false)->comment('Prodi default yang dipilih saat login');
            $table->timestamps();

            // Composite unique key: satu user tidak bisa punya prodi yang sama 2x
            $table->unique(['user_id', 'kode_prodi'], 'user_prodi_unique');

            // Foreign key ke users table
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Indexes untuk performance
            $table->index('user_id', 'idx_user_prodi_user');
            $table->index('kode_prodi', 'idx_user_prodi_kode');
            $table->index(['user_id', 'is_default'], 'idx_user_prodi_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_prodi');
    }
};
