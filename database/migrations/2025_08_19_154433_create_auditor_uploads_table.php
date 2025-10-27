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
        Schema::create('auditor_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jadwal_ami_id');
            $table->unsignedBigInteger('auditor_id');
            $table->string('original_name'); // Nama file asli
            $table->string('stored_name'); // Nama file di server (auto-generated)
            $table->string('file_path');
            $table->bigInteger('file_size'); // Size dalam bytes
            $table->string('file_type', 50); // pdf, doc, docx
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('jadwal_ami_id')->references('id')->on('jadwal_ami')->onDelete('cascade');
            $table->foreign('auditor_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['jadwal_ami_id', 'auditor_id']);
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditor_uploads');
    }
};