<?php

namespace App\Repositories\PemenuhanDokumen;

use App\Models\KriteriaDokumen;
use App\Models\JadwalAmi;
use Illuminate\Support\Facades\Auth;
use App\Models\PemenuhanDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KriteriaDokumenRepository
{
    public function getKriteriaDokumenWithDetails($filters = [], $perPage = 10, $page = 1)
    {
        $user = Auth::user();
        $activeRole = session('active_role');

        // ========== DEBUG LOG START ==========
        Log::info('=== KRITERIA DOKUMEN DEBUG START ===');
        Log::info('USER INFO', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_prodi' => $user->prodi,
            'user_fakultas' => $user->fakultas,
            'active_role' => $activeRole
        ]);
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

        if ($activeRole === 'Admin PPG') {
            $ppgJenjangIds = \App\Models\Jenjang::where(function ($q) {
                $q->where('nama', 'like', '%profesi%')
                  ->orWhere('nama', 'like', '%ppg%')
                  ->orWhere('nama', 'like', '%program%')
                  ->orWhere('nama', 'like', '%pp%');
            })->pluck('id')->toArray();

            if (!empty($ppgJenjangIds)) {
                $subQuery->whereIn('jenjang_id', $ppgJenjangIds);
            } else {
                $subQuery->whereRaw('1 = 0');
            }
        }

        // PERBAIKAN: Skip filter Admin Prodi jika role adalah Admin PPG (untuk avoid konflik)
        // PERUBAHAN: Untuk Admin Prodi dengan prodi PPG/Profesi
        if (!$isSuperAdmin && !$isAdminLPM && !$isFakultas && !$isAuditor && $activeRole !== 'Admin PPG') {
            $userProdi = $user->prodi;
            if ($userProdi) {
                // BARU: Deteksi apakah prodi ini adalah PPG/Profesi
                $isProfesiProdi = $this->isProfesiProdi($userProdi);
                
                if ($isProfesiProdi) {
                    // BARU: Untuk prodi PPG/Profesi, ambil SEMUA jenjang profesi yang ada
                    $allProfesiJenjang = $this->getAllProfesiJenjang();
                    
                    if (!empty($allProfesiJenjang)) {
                        $subQuery->whereHas('jenjang', function ($q) use ($allProfesiJenjang) {
                            $q->whereIn('nama', $allProfesiJenjang);
                        });
                    }
                } else {
                    // Untuk prodi non-profesi, gunakan logic lama
                    $userJenjang = $this->detectJenjangFromProdi($userProdi);
                    
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
            // Dapatkan jadwal aktif auditor
            $activeJadwal = JadwalAmi::whereHas('timAuditor', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();

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
            $userFakultas = $user->fakultas;
            if ($userFakultas) {
                // Ambil prodi dari fakultas ini
                $prodiList = JadwalAmi::where('fakultas', $userFakultas)
                    ->pluck('prodi')
                    ->toArray();

                if (!empty($prodiList)) {
                    $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($prodiList) {
                        $q->whereIn('prodi', $prodiList);
                    });
                }
            }
        }
        // TAMBAHKAN DI SINI - setelah elseif ($isFakultas)
        elseif ($activeRole === 'Admin PPG') {
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

            if (!empty($ppgProdiList)) {
                $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($ppgProdiList) {
                    $q->whereIn('prodi', $ppgProdiList);
                });
            }
        }
        // Filter untuk Admin (non Super Admin, non Admin LPM, dan non Fakultas)
        elseif (!$isSuperAdmin && !$isAdminLPM && !$isFakultas) {
            $userProdi = $user->prodi;
            if ($userProdi) {
                $kodeProdi = trim(explode('-', $userProdi)[0]);
                
                // PERUBAHAN: Deteksi apakah prodi ini PPG/Profesi
                $isProfesiProdi = $this->isProfesiProdi($userProdi);
                
                if ($isProfesiProdi) {
                    // BARU: Untuk prodi PPG/Profesi, ambil SEMUA jenjang profesi
                    $allProfesiJenjang = $this->getAllProfesiJenjang();
                    
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

        // Pagination dengan explicit page parameter
        $result = $query->orderBy('periode_atau_tahun', 'desc')
            ->orderBy('jenjang_id', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Log hasil pagination
        \Illuminate\Support\Facades\Log::info('Repository pagination result', [
            'perPage_used' => $perPage,
            'total_results' => $result ? $result->total() : 0,
            'last_page' => $result ? $result->lastPage() : 0,
            'current_page' => $result ? $result->currentPage() : 0
        ]);

        return $result;
    }

    public function getAllKriteriaDokumenWithDetails($filters = [])
    {
        $user = Auth::user();
        $activeRole = session('active_role');

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

        if ($activeRole === 'Admin PPG') {
            $ppgJenjangIds = \App\Models\Jenjang::where(function ($q) {
                $q->where('nama', 'like', '%profesi%')
                  ->orWhere('nama', 'like', '%ppg%')
                  ->orWhere('nama', 'like', '%program%')
                  ->orWhere('nama', 'like', '%pp%');
            })->pluck('id')->toArray();

            if (!empty($ppgJenjangIds)) {
                $subQuery->whereIn('jenjang_id', $ppgJenjangIds);
            } else {
                $subQuery->whereRaw('1 = 0');
            }
        }

        // PERBAIKAN: Skip filter Admin Prodi jika role adalah Admin PPG (untuk avoid konflik)
        // PERUBAHAN: Untuk Admin Prodi dengan prodi PPG/Profesi
        if (!$isSuperAdmin && !$isAdminLPM && !$isFakultas && !$isAuditor && $activeRole !== 'Admin PPG') {
            $userProdi = $user->prodi;
            if ($userProdi) {
                // BARU: Deteksi apakah prodi ini adalah PPG/Profesi
                $isProfesiProdi = $this->isProfesiProdi($userProdi);
                
                if ($isProfesiProdi) {
                    // BARU: Untuk prodi PPG/Profesi, ambil SEMUA jenjang profesi yang ada
                    $allProfesiJenjang = $this->getAllProfesiJenjang();
                    
                    if (!empty($allProfesiJenjang)) {
                        $subQuery->whereHas('jenjang', function ($q) use ($allProfesiJenjang) {
                            $q->whereIn('nama', $allProfesiJenjang);
                        });
                    }
                } else {
                    // Untuk prodi non-profesi, gunakan logic lama
                    $userJenjang = $this->detectJenjangFromProdi($userProdi);
                    
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
            // Dapatkan jadwal aktif auditor
            $activeJadwal = JadwalAmi::whereHas('timAuditor', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();

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
            $userFakultas = $user->fakultas;
            if ($userFakultas) {
                // Ambil prodi dari fakultas ini
                $prodiList = JadwalAmi::where('fakultas', $userFakultas)
                    ->pluck('prodi')
                    ->toArray();

                if (!empty($prodiList)) {
                    $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($prodiList) {
                        $q->whereIn('prodi', $prodiList);
                    });
                }
            }
        }
        // TAMBAHKAN DI SINI - setelah elseif ($isFakultas)
        elseif ($activeRole === 'Admin PPG') {
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

            if (!empty($ppgProdiList)) {
                $query->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function ($q) use ($ppgProdiList) {
                    $q->whereIn('prodi', $ppgProdiList);
                });
            }
            // TAMBAH LOG HASIL SETELAH FILTER
            $afterPPGFilter = $query->get();
        }
        // Filter untuk Admin (non Super Admin, non Admin LPM, dan non Fakultas)
        elseif (!$isSuperAdmin && !$isAdminLPM && !$isFakultas) {
            $userProdi = $user->prodi;
            if ($userProdi) {
                $kodeProdi = trim(explode('-', $userProdi)[0]);
                
                // PERUBAHAN: Deteksi apakah prodi ini PPG/Profesi
                $isProfesiProdi = $this->isProfesiProdi($userProdi);
                
                if ($isProfesiProdi) {
                    // BARU: Untuk prodi PPG/Profesi, ambil SEMUA jenjang profesi
                    $allProfesiJenjang = $this->getAllProfesiJenjang();
                    
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

        return $result;
    }

    public function getHeaderData($lembagaId, $jenjangId)
    {
        return KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
            ->where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->first();
    }

    public function getKriteriaDokumenWithCapaian($lembagaId, $jenjangId, $selectedProdi)
    {
        $kriteriaDokumen = KriteriaDokumen::with([
            'judulKriteriaDokumen',
            'kelolaKebutuhanKriteriaDokumen'
        ])
            ->where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->get()
            ->groupBy('judulKriteriaDokumen.nama_kriteria_dokumen');

        foreach ($kriteriaDokumen as $items) {
            foreach ($items as $item) {
                $item->capaian_dokumen = PemenuhanDokumen::where('kriteria_dokumen_id', $item->id)
                    ->where('prodi', $selectedProdi)
                    ->count();
                $item->total_kebutuhan = $item->kebutuhan_dokumen;
            }
        }

        return $kriteriaDokumen;
    }

    // ========== FUNGSI BARU ==========

    /**
     * FUNGSI BARU: Deteksi SEMUA jenjang yang relevan untuk sebuah prodi
     * Untuk prodi PPG/Profesi, return ARRAY semua jenjang profesi
     * Untuk prodi lain, return ARRAY dengan 1 jenjang saja
     *
     * @param string $prodiString
     * @return array Array of jenjang names
     */
    protected function getAllRelevantJenjang($prodiString)
    {
        if (empty($prodiString)) {
            return [];
        }

        // Cek apakah ini prodi profesi
        $isProfesiProdi = $this->isProfesiProdi($prodiString);

        if ($isProfesiProdi) {
            // Untuk prodi profesi, ambil SEMUA jenjang profesi yang ada
            $allProfesiJenjang = $this->getAllProfesiJenjang();
            return !empty($allProfesiJenjang) ? $allProfesiJenjang : [];
        } else {
            // Untuk prodi non-profesi, gunakan deteksi jenjang biasa
            $singleJenjang = $this->detectJenjangFromProdi($prodiString);
            return $singleJenjang ? [$singleJenjang] : [];
        }
    }

    /**
     * Cek apakah prodi adalah PPG/Profesi
     */
    protected function isProfesiProdi($prodiString)
    {
        if (empty($prodiString)) {
            return false;
        }
        
        $lowercaseProdi = strtolower($prodiString);
        
        $profesiPatterns = [
            'profesi', 'ppg', 'program profesi', 'pp', 'program'
        ];
        
        foreach ($profesiPatterns as $pattern) {
            if (strpos($lowercaseProdi, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ambil SEMUA jenjang profesi yang ada di database
     * Return: array of jenjang names
     */
    protected function getAllProfesiJenjang()
    {
        // Cari semua jenjang yang mengandung keyword profesi
        $jenjangList = \DB::table('jenjang')
            ->where(function($query) {
                $query->where('nama', 'LIKE', '%profesi%')
                      ->orWhere('nama', 'LIKE', '%PROFESI%')
                      ->orWhere('nama', 'LIKE', '%PPG%')
                      ->orWhere('nama', 'LIKE', '%PP%')
                      ->orWhere('nama', 'LIKE', '%Profesi%')
                      ->orWhere('nama', 'LIKE', '%Program Profesi%')
                      ->orWhere('nama', 'LIKE', '%PROGRAM%')
                      ->orWhere('nama', '=', 'PP')
                      ->orWhere('nama', '=', 'PPG')
                      ->orWhere('nama', '=', 'PROFESI');
            })
            ->pluck('nama')
            ->toArray();
        
        return $jenjangList;
    }

    // ========== FUNGSI LAMA (TETAP ADA) ==========
    
    /**
     * Fungsi helper untuk mendeteksi jenjang dari string prodi
     * NOTE: Fungsi ini masih dipakai untuk role Auditor dan logic lain
     */
    protected function detectJenjangFromProdi($prodiString)
    {
        if (empty($prodiString)) {
            return null;
        }

        // PRIORITAS 1: Format jenjang dalam kurung (S1), (S2), (D3), dll
        if (preg_match('/\((S[0-9]+|D[0-9]+)\)/', $prodiString, $matches)) {
            return trim($matches[1]);
        }

        // PRIORITAS 2: Pattern profesi → CEK DATABASE
        $lowercaseProdi = strtolower($prodiString);

        $isProfesiPattern = false;

        $profesiPatterns = [
            'profesi',
            'ppg',
            'program profesi',
            'PPG',
            'PP',
            'Profesi',
            'Program Profesi',
            'PROGRAM'
        ];

        foreach ($profesiPatterns as $pattern) {
            if (
                strpos($lowercaseProdi, strtolower($pattern)) !== false ||
                strpos($prodiString, $pattern) !== false
            ) {
                $isProfesiPattern = true;
                break;
            }
        }

        if ($isProfesiPattern) {
            return $this->findAvailableProfesiJenjang();
        }

        return null;
    }

    protected function findAvailableProfesiJenjang()
    {
        $possibleJenjangNames = [
            'PROFESI',
            'PP',
            'PPG',
            'PROGRAM',
            'Program Profesi',
            'Profesi'
        ];

        foreach ($possibleJenjangNames as $jenjangName) {
            $exists = \DB::table('jenjang')->where('nama', $jenjangName)->exists();
            if ($exists) {
                return $jenjangName;
            }
        }

        // Fallback dengan LIKE
        $fallbackJenjang = \DB::table('jenjang')
            ->where(function ($query) {
                $query->where('nama', 'LIKE', '%profesi%')
                    ->orWhere('nama', 'LIKE', '%PROFESI%')
                    ->orWhere('nama', 'LIKE', '%PPG%')
                    ->orWhere('nama', 'LIKE', '%PP%');
            })
            ->first();

        if ($fallbackJenjang) {
            return $fallbackJenjang->nama;
        }

        return null;
    }
}