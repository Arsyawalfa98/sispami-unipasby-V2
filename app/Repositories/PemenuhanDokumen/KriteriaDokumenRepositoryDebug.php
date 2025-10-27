<?php

namespace App\Repositories\PemenuhanDokumen;

use App\Models\KriteriaDokumen;
use App\Models\JadwalAmi;
use Illuminate\Support\Facades\Auth;
use App\Models\PemenuhanDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DEBUG REPOSITORY - UNTUK DEBUGGING MASALAH AUDITOR & ADMIN PPG
 *
 * File ini adalah copy dari KriteriaDokumenRepository.php dengan penambahan
 * extensive logging untuk debug masalah.
 *
 * Cara menggunakan:
 * 1. Ganti dependency injection di PemenuhanDokumenService dari
 *    KriteriaDokumenRepository ke KriteriaDokumenRepositoryDebug
 * 2. Akses halaman sebagai Auditor atau Admin PPG
 * 3. Cek storage/logs/laravel.log untuk melihat debug output
 * 4. Setelah selesai debug, kembalikan ke KriteriaDokumenRepository
 */
class KriteriaDokumenRepositoryDebug extends KriteriaDokumenRepository
{
    public function getAllKriteriaDokumenWithDetails($filters = [])
    {
        $user = Auth::user();
        $activeRole = session('active_role');

        // ========== DEBUG LOG START ==========
        Log::info('========================================');
        Log::info('=== KRITERIA DOKUMEN DEBUG START ===');
        Log::info('========================================');
        Log::info('USER INFO', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_prodi' => $user->prodi,
            'user_fakultas' => $user->fakultas,
            'active_role' => $activeRole
        ]);
        Log::info('FILTERS', $filters);
        // ========== DEBUG LOG END ==========

        // Subquery untuk mendapatkan ID terbaru
        $subQuery = KriteriaDokumen::select([
            'lembaga_akreditasi_id',
            'jenjang_id',
            'periode_atau_tahun',
            \DB::raw('MAX(id) as id')
        ]);

        // Filter subquery berdasarkan role
        $isSuperAdmin = $activeRole === 'Super Admin';
        $isAdminLPM = $activeRole === 'Admin LPM';
        $isAuditor = $activeRole === 'Auditor';
        $isFakultas = $activeRole === 'Fakultas';

        Log::info('ROLE FLAGS', [
            'isSuperAdmin' => $isSuperAdmin,
            'isAdminLPM' => $isAdminLPM,
            'isAuditor' => $isAuditor,
            'isFakultas' => $isFakultas,
            'isAdminPPG' => $activeRole === 'Admin PPG'
        ]);

        if ($activeRole === 'Admin PPG') {
            Log::info('=== ADMIN PPG BRANCH ===');

            $ppgJenjangIds = \App\Models\Jenjang::where(function ($q) {
                $q->where('nama', 'like', '%profesi%')
                  ->orWhere('nama', 'like', '%ppg%')
                  ->orWhere('nama', 'like', '%program%')
                  ->orWhere('nama', 'like', '%pp%');
            })->pluck('id')->toArray();

            Log::info('PPG JENJANG IDS FOUND', [
                'jenjang_ids' => $ppgJenjangIds,
                'total' => count($ppgJenjangIds)
            ]);

            // Get jenjang names for clarity
            $ppgJenjangNames = \App\Models\Jenjang::whereIn('id', $ppgJenjangIds)
                ->pluck('nama', 'id')
                ->toArray();

            Log::info('PPG JENJANG NAMES', $ppgJenjangNames);

            if (!empty($ppgJenjangIds)) {
                $subQuery->whereIn('jenjang_id', $ppgJenjangIds);
            } else {
                $subQuery->whereRaw('1 = 0');
            }
        }

        // PERBAIKAN: Skip filter Admin Prodi jika role adalah Admin PPG (untuk avoid konflik)
        // PERUBAHAN: Untuk Admin Prodi dengan prodi PPG/Profesi
        if (!$isSuperAdmin && !$isAdminLPM && !$isFakultas && !$isAuditor && $activeRole !== 'Admin PPG') {
            Log::info('=== ADMIN PRODI BRANCH ===');

            $userProdi = $user->prodi;
            if ($userProdi) {
                Log::info('USER PRODI', ['prodi' => $userProdi]);

                // BARU: Deteksi apakah prodi ini adalah PPG/Profesi
                $isProfesiProdi = $this->isProfesiProdi($userProdi);

                Log::info('IS PROFESI PRODI?', ['result' => $isProfesiProdi]);

                if ($isProfesiProdi) {
                    // BARU: Untuk prodi PPG/Profesi, ambil SEMUA jenjang profesi yang ada
                    $allProfesiJenjang = $this->getAllProfesiJenjang();

                    Log::info('ALL PROFESI JENJANG FOUND', [
                        'jenjang_list' => $allProfesiJenjang,
                        'total' => count($allProfesiJenjang)
                    ]);

                    if (!empty($allProfesiJenjang)) {
                        $subQuery->whereHas('jenjang', function ($q) use ($allProfesiJenjang) {
                            $q->whereIn('nama', $allProfesiJenjang);
                        });
                    }
                } else {
                    // Untuk prodi non-profesi, gunakan logic lama
                    $userJenjang = $this->detectJenjangFromProdi($userProdi);

                    Log::info('NON-PROFESI - DETECTED JENJANG', ['jenjang' => $userJenjang]);

                    if ($userJenjang) {
                        $subQuery->whereHas('jenjang', function ($q) use ($userJenjang) {
                            $q->where('nama', $userJenjang);
                        });
                    }
                }
            }
        }

        $subQuery = $subQuery->groupBy('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun');

        // Query utama
        $query = KriteriaDokumen::with([
            'lembagaAkreditasi.lembagaAkreditasiDetail',
            'jenjang'
        ])
            ->whereIn('id', $subQuery->pluck('id'));

        // Apply search filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('lembagaAkreditasi', function ($q) use ($filters) {
                    $q->where('nama', 'like', "%{$filters['search']}%");
                });
            });
        }

        // Filter untuk fakultas jika ada dalam filters
        if (isset($filters['fakultas']) && !empty($filters['fakultas'])) {
            $fakultas = $filters['fakultas'];

            // Ambil daftar prodi yang terkait dengan fakultas ini
            $prodiList = JadwalAmi::where('fakultas', $fakultas)
                ->pluck('prodi')
                ->toArray();

            if (!empty($prodiList)) {
                $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($prodiList) {
                    $q->whereIn('prodi', $prodiList);
                });
            }
        }

        // Filter berdasarkan role
        if ($isAuditor) {
            Log::info('=== AUDITOR FILTER BRANCH ===');

            // Dapatkan jadwal aktif auditor
            $activeJadwal = JadwalAmi::whereHas('timAuditor', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();

            Log::info('AUDITOR JADWAL AMI', [
                'total_jadwal' => $activeJadwal->count(),
                'jadwal_list' => $activeJadwal->map(function ($j) {
                    return [
                        'id' => $j->id,
                        'prodi' => $j->prodi,
                        'standar_akreditasi' => $j->standar_akreditasi,
                        'periode' => $j->periode,
                        'tanggal_mulai' => $j->tanggal_mulai,
                        'tanggal_selesai' => $j->tanggal_selesai
                    ];
                })->toArray()
            ]);

            if ($activeJadwal->isNotEmpty()) {
                // PERBAIKAN: Gunakan getAllRelevantJenjang untuk mendukung multi-jenjang
                $prodiDetails = $activeJadwal->map(function ($jadwal) {
                    $jenjangList = $this->getAllRelevantJenjang($jadwal->prodi); // ARRAY jenjang

                    return [
                        'prodi' => $jadwal->prodi,
                        'jenjang_list' => $jenjangList, // UBAH: dari 'jenjang' ke 'jenjang_list'
                        'lembaga' => $jadwal->standar_akreditasi
                    ];
                });

                Log::info('AUDITOR PRODI DETAILS (AFTER getAllRelevantJenjang)', [
                    'prodi_details' => $prodiDetails->toArray()
                ]);

                $query->where(function ($q) use ($prodiDetails) {
                    foreach ($prodiDetails as $detail) {
                        if (empty($detail['jenjang_list'])) continue; // Skip jika tidak ada jenjang

                        // PERBAIKAN: Loop untuk setiap jenjang yang relevan
                        foreach ($detail['jenjang_list'] as $jenjang) {
                            $q->orWhere(function ($subQ) use ($detail, $jenjang) {
                                $subQ->whereHas('jenjang', function ($jQ) use ($jenjang) {
                                    $jQ->where('nama', $jenjang); // Cari berdasarkan setiap jenjang
                                })
                                    ->whereHas('lembagaAkreditasi', function ($lQ) use ($detail) {
                                        $lQ->where('nama', $detail['lembaga'])
                                            ->whereHas('lembagaAkreditasiDetail', function ($dQ) use ($detail) {
                                                $dQ->where('prodi', $detail['prodi']);
                                            });
                                    });
                            });
                        }
                    }
                });
            }
        }
        // Filter untuk Fakultas
        elseif ($isFakultas) {
            Log::info('=== FAKULTAS FILTER BRANCH ===');

            $userFakultas = $user->fakultas;
            if ($userFakultas) {
                // Ambil prodi dari fakultas ini
                $prodiList = JadwalAmi::where('fakultas', $userFakultas)
                    ->pluck('prodi')
                    ->toArray();

                Log::info('FAKULTAS PRODI LIST', [
                    'fakultas' => $userFakultas,
                    'prodi_list' => $prodiList,
                    'total' => count($prodiList)
                ]);

                if (!empty($prodiList)) {
                    $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($prodiList) {
                        $q->whereIn('prodi', $prodiList);
                    });
                }
            }
        }
        // TAMBAHKAN DI SINI - setelah elseif ($isFakultas)
        elseif ($activeRole === 'Admin PPG') {
            Log::info('=== ADMIN PPG FILTER (MAIN QUERY) ===');

            // Ambil prodi PPG dari jadwal
            $ppgProdiList = JadwalAmi::where(function ($q) {
                $q->where('prodi', 'like', '%profesi%')
                    ->orWhere('prodi', 'like', '%ppg%')
                    ->orWhere('prodi', 'like', '%program profesi%')
                    ->orWhere('prodi', 'like', '%PPG%')
                    ->orWhere('prodi', 'like', '%PP%')
                    ->orWhere('prodi', 'like', '%Profesi%')
                    ->orWhere('prodi', 'like', '%Program Profesi%')
                    ->orWhere('prodi', 'like', '%PROGRAM%');
            })->pluck('prodi')->toArray();

            Log::info('PPG PRODI LIST FROM JADWAL AMI', [
                'prodi_list' => $ppgProdiList,
                'total' => count($ppgProdiList)
            ]);

            if (!empty($ppgProdiList)) {
                $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($ppgProdiList) {
                    $q->whereIn('prodi', $ppgProdiList);
                });
            }
            // TAMBAH LOG HASIL SETELAH FILTER
            $afterPPGFilter = $query->get();

            Log::info('AFTER PPG FILTER RESULTS', [
                'total_results' => $afterPPGFilter->count(),
                'results' => $afterPPGFilter->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'lembaga' => $item->lembagaAkreditasi->nama ?? 'N/A',
                        'jenjang' => $item->jenjang->nama ?? 'N/A',
                        'periode' => $item->periode_atau_tahun
                    ];
                })->toArray()
            ]);
        }
        // Filter untuk Admin (non Super Admin, non Admin LPM, dan non Fakultas)
        elseif (!$isSuperAdmin && !$isAdminLPM && !$isFakultas) {
            Log::info('=== ADMIN PRODI FILTER (MAIN QUERY) ===');

            $userProdi = $user->prodi;
            if ($userProdi) {
                $kodeProdi = trim(explode('-', $userProdi)[0]);

                Log::info('KODE PRODI EXTRACTED', ['kode_prodi' => $kodeProdi]);

                // PERUBAHAN: Deteksi apakah prodi ini PPG/Profesi
                $isProfesiProdi = $this->isProfesiProdi($userProdi);

                if ($isProfesiProdi) {
                    // BARU: Untuk prodi PPG/Profesi, ambil SEMUA jenjang profesi
                    $allProfesiJenjang = $this->getAllProfesiJenjang();

                    Log::info('PROFESI PRODI - FILTER WITH ALL JENJANG', [
                        'all_jenjang' => $allProfesiJenjang
                    ]);

                    if (!empty($allProfesiJenjang)) {
                        $query->whereHas('jenjang', function ($q) use ($allProfesiJenjang) {
                            $q->whereIn('nama', $allProfesiJenjang);
                        });

                        // Filter berdasarkan kode prodi
                        $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($kodeProdi) {
                            $q->where('prodi', 'like', "$kodeProdi%");
                        });
                    }
                } else {
                    // Untuk prodi non-profesi, gunakan logic lama
                    $userJenjang = $this->detectJenjangFromProdi($userProdi);

                    Log::info('NON-PROFESI PRODI - FILTER WITH SINGLE JENJANG', [
                        'detected_jenjang' => $userJenjang
                    ]);

                    if ($userJenjang) {
                        $query->whereHas('jenjang', function ($q) use ($userJenjang) {
                            $q->where('nama', $userJenjang);
                        });

                        $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($kodeProdi, $userJenjang) {
                            $q->where(function ($subQ) use ($kodeProdi, $userJenjang) {
                                $subQ->where('prodi', 'like', "$kodeProdi%")
                                    ->where('prodi', 'like', "%($userJenjang)%");
                            });
                        });
                    }
                }
            }
        }

        // Ambil semua data tanpa pagination
        $result = $query->orderBy('periode_atau_tahun', 'desc')
            ->orderBy('jenjang_id', 'asc')
            ->get();

        Log::info('FINAL QUERY RESULTS', [
            'total_results' => $result->count(),
            'results_summary' => $result->map(function ($item) {
                return [
                    'id' => $item->id,
                    'lembaga' => $item->lembagaAkreditasi->nama ?? 'N/A',
                    'jenjang' => $item->jenjang->nama ?? 'N/A',
                    'periode' => $item->periode_atau_tahun,
                    'registered_prodi_count' => $item->lembagaAkreditasi->lembagaAkreditasiDetail->count() ?? 0
                ];
            })->toArray()
        ]);

        Log::info('========================================');
        Log::info('=== KRITERIA DOKUMEN DEBUG END ===');
        Log::info('========================================');

        return $result;
    }
}
