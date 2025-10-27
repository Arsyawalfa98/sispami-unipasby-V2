<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom jenjang dari ENUM ke VARCHAR(100)
        DB::statement("ALTER TABLE program_studi MODIFY COLUMN jenjang VARCHAR(100)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke ENUM semula
        DB::statement("ALTER TABLE program_studi MODIFY COLUMN jenjang ENUM('S1','S2','S3','D4','D3')");
    }
};