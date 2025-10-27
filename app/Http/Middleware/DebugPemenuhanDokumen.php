<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\JadwalAmi;
use App\Models\KriteriaDokumen;

class DebugPemenuhanDokumen
{
    public function handle(Request $request, Closure $next)
    {
        // Hanya jalankan untuk route pemenuhan-dokumen
        if (!$request->is('pemenuhan-dokumen*')) {
            return $next($request);
        }

        $user = Auth::user();
        $activeRole = session('active_role');

        Log::info('==============================================');
        Log::info('DEBUG PEMENUHAN DOKUMEN - FULL TRACE');
        Log::info('==============================================');

        // 1. INFO USER
        Log::info('1. USER INFO', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_prodi' => $user->prodi,
            'user_fakultas' => $user->fakultas,
            'active_role' => $activeRole
        ]);

        // 2. JADWAL AMI - User di plotting dimana?
        $allJadwal = JadwalAmi::with('timAuditor')->get();

        $userJadwal = $allJadwal->filter(function($jadwal) use ($user) {
            return $jadwal->timAuditor->pluck('id')->contains($user->id);
        });

        Log::info('2. JADWAL AMI - USER PLOTTING', [
            'total_jadwal_user' => $userJadwal->count(),
            'detail_jadwal' => $userJadwal->map(function($j) use ($user) {
                $auditorInfo = $j->timAuditor->where('id', $user->id)->first();
                return [
                    'id' => $j->id,
                    'prodi' => $j->prodi,
                    'fakultas' => $j->fakultas,
                    'standar_akreditasi' => $j->standar_akreditasi,
                    'periode' => $j->periode,
                    'tanggal_mulai' => $j->tanggal_mulai,
                    'tanggal_selesai' => $j->tanggal_selesai,
                    'role_di_jadwal' => $auditorInfo ? $auditorInfo->pivot->role_auditor : 'N/A'
                ];
            })->toArray()
        ]);

        // 3. SEMUA JADWAL AMI (untuk Super Admin)
        if ($activeRole === 'Super Admin') {
            Log::info('3. SEMUA JADWAL AMI (Super Admin)', [
                'total_semua_jadwal' => $allJadwal->count(),
                'jadwal_profesi' => $allJadwal->filter(function($j) {
                    $prodi = strtolower($j->prodi);
                    return strpos($prodi, 'profesi') !== false ||
                           strpos($prodi, 'ppg') !== false ||
                           strpos($prodi, 'program profesi') !== false;
                })->map(function($j) {
                    return [
                        'id' => $j->id,
                        'prodi' => $j->prodi,
                        'fakultas' => $j->fakultas,
                        'standar_akreditasi' => $j->standar_akreditasi,
                        'periode' => $j->periode
                    ];
                })->values()->toArray()
            ]);
        }

        // 4. KRITERIA DOKUMEN - Yang tersedia
        $allKriteria = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
            ->select('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun')
            ->groupBy('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun')
            ->get();

        Log::info('4. KRITERIA DOKUMEN TERSEDIA', [
            'total_kriteria_group' => $allKriteria->count(),
            'detail_kriteria' => $allKriteria->map(function($k) {
                return [
                    'lembaga' => $k->lembagaAkreditasi->nama ?? 'N/A',
                    'jenjang' => $k->jenjang->nama ?? 'N/A',
                    'periode' => $k->periode_atau_tahun,
                    'total_prodi_terdaftar' => $k->lembagaAkreditasi->lembagaAkreditasiDetail->count() ?? 0
                ];
            })->toArray()
        ]);

        // 5. KRITERIA PROFESI - Khusus check
        $kriteriaProfesi = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
            ->whereHas('jenjang', function($q) {
                $q->where('nama', 'LIKE', '%profesi%')
                  ->orWhere('nama', 'LIKE', '%PPG%')
                  ->orWhere('nama', 'LIKE', '%PP%');
            })
            ->select('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun')
            ->groupBy('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun')
            ->get();

        Log::info('5. KRITERIA PROFESI/PPG TERSEDIA', [
            'total_kriteria_profesi' => $kriteriaProfesi->count(),
            'detail' => $kriteriaProfesi->map(function($k) {
                return [
                    'lembaga' => $k->lembagaAkreditasi->nama ?? 'N/A',
                    'jenjang' => $k->jenjang->nama ?? 'N/A',
                    'periode' => $k->periode_atau_tahun,
                    'prodi_terdaftar' => $k->lembagaAkreditasi->lembagaAkreditasiDetail->pluck('prodi')->toArray() ?? []
                ];
            })->toArray()
        ]);

        // 6. MATCHING - Jadwal vs Kriteria
        Log::info('6. MATCHING ANALYSIS - JADWAL vs KRITERIA');

        foreach ($userJadwal as $jadwal) {
            $matchingKriteria = $allKriteria->filter(function($k) use ($jadwal) {
                // Check lembaga match
                $lembagaMatch = $k->lembagaAkreditasi->nama === $jadwal->standar_akreditasi;

                // Check periode match (tahun)
                $periodeMatch = substr($k->periode_atau_tahun, 0, 4) === substr($jadwal->periode, 0, 4);

                // Check prodi terdaftar
                $prodiTerdaftar = false;
                if ($k->lembagaAkreditasi->lembagaAkreditasiDetail) {
                    foreach ($k->lembagaAkreditasi->lembagaAkreditasiDetail as $detail) {
                        $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                        $detailKode = trim(explode('-', $detail->prodi)[0]);
                        if ($jadwalKode === $detailKode) {
                            $prodiTerdaftar = true;
                            break;
                        }
                    }
                }

                return $lembagaMatch && $periodeMatch && $prodiTerdaftar;
            });

            Log::info('  JADWAL: ' . $jadwal->prodi, [
                'standar_akreditasi' => $jadwal->standar_akreditasi,
                'periode' => $jadwal->periode,
                'matching_kriteria_count' => $matchingKriteria->count(),
                'matching_kriteria_detail' => $matchingKriteria->map(function($k) {
                    return [
                        'lembaga' => $k->lembagaAkreditasi->nama,
                        'jenjang' => $k->jenjang->nama,
                        'periode' => $k->periode_atau_tahun
                    ];
                })->toArray()
            ]);
        }

        Log::info('==============================================');
        Log::info('END DEBUG');
        Log::info('==============================================');

        return $next($request);
    }
}
