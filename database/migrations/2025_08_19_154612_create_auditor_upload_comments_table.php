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
        Schema::create('auditor_upload_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jadwal_ami_id');
            $table->unsignedBigInteger('admin_id'); // User yang memberikan komentar (Admin LPM)
            $table->text('komentar');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('jadwal_ami_id')->references('id')->on('jadwal_ami')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index
            $table->index('jadwal_ami_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditor_upload_comments');
    }
};