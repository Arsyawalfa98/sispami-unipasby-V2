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
        Schema::table('jadwal_ami_auditor', function (Blueprint $table) {
            $table->enum('role_auditor', ['ketua', 'anggota'])->default('anggota')->after('user_id');
        });

        // Set auditor pertama sebagai ketua untuk data existing
        $this->setExistingKetuaAuditor();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_ami_auditor', function (Blueprint $table) {
            $table->dropColumn('role_auditor');
        });
    }

    /**
     * Set auditor pertama sebagai ketua untuk setiap jadwal AMI yang sudah ada
     */
    private function setExistingKetuaAuditor(): void
    {
        // Ambil auditor pertama (berdasarkan ID terkecil) untuk setiap jadwal
        $firstAuditors = DB::table('jadwal_ami_auditor')
            ->select('jadwal_ami_id', DB::raw('MIN(id) as first_auditor_id'))
            ->groupBy('jadwal_ami_id')
            ->get();

        // Set sebagai ketua
        foreach ($firstAuditors as $auditor) {
            DB::table('jadwal_ami_auditor')
                ->where('id', $auditor->first_auditor_id)
                ->update(['role_auditor' => 'ketua']);
        }
    }
};