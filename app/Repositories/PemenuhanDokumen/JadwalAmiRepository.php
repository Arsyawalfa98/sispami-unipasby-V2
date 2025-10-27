<?php

namespace App\Repositories\PemenuhanDokumen;

use App\Models\JadwalAmi;
use App\Models\LembagaAkreditasiDetail;
use Illuminate\Support\Facades\Log;
use App\Services\PemenuhanDokumen\StatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JadwalAmiRepository
{
    private const PPG_PATTERNS = [
        'profesi',
        'ppg',
        'program profesi',
        'PPG',
        'PP',
        'Profesi',
        'Program Profesi',
        'PROGRAM'
    ];
    public function getActiveJadwal($userProdi = null)
    {
        $user = Auth::user();
        $activeRole = session('active_role');

        // Log informasi user dan role untuk debugging
        // Log::info('getActiveJadwal Request', [
        //     'user_id' => $user->id,
        //     'user_name' => $user->name,
        //     'user_prodi' => $user->prodi,
        //     'user_fakultas' => $user->fakultas,
        //     'active_role' => $activeRole,
        //     'requested_prodi' => $userProdi
        // ]);

        // Cek role dengan manual logging
        $isSuperAdmin = $activeRole === 'Super Admin';
        $isAdminLPM = $activeRole === 'Admin LPM';
        $isAuditor = $activeRole === 'Auditor';
        $isFakultas = $activeRole === 'Fakultas';

        // Log::info('Role Check Results', [
        //     'isSuperAdmin' => $isSuperAdmin,
        //     'isAdminLPM' => $isAdminLPM,
        //     'isAuditor' => $isAuditor,
        //     'isFakultas' => $isFakultas
        // ]);

        // EDIT: Start query dengan pivot data untuk role_auditor
        $query = JadwalAmi::with(['timAuditor' => function ($query) {
            $query->withPivot('role_auditor');
        }]);

        // Tidak ada filter untuk Super Admin dan Admin LPM
        if ($isSuperAdmin || $isAdminLPM) {
            // Log::info('No filter applied - Admin role detected');
        }
        // Filter untuk role Fakultas
        elseif ($isFakultas) {
            $userFakultas = $user->fakultas;
            // Log::info('Applying Fakultas filter', ['fakultas' => $userFakultas]);
            $query->where('fakultas', $userFakultas);
        }
        // Filter untuk role Auditor
        elseif ($isAuditor) {
            // Log::info('Applying Auditor filter', ['user_id' => $user->id]);
            $query->whereHas('timAuditor', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($activeRole === 'Admin PPG') {
            $query->where(function ($q) {
                $q->where('prodi', 'like', '%profesi%')
                    ->orWhere('prodi', 'like', '%ppg%')
                    ->orWhere('prodi', 'like', '%program profesi%')
                    ->orWhere('prodi', 'like', '%PPG%')
                    ->orWhere('prodi', 'like', '%PP%')
                    ->orWhere('prodi', 'like', '%Profesi%')
                    ->orWhere('prodi', 'like', '%Program Profesi%')
                    ->orWhere('prodi', 'like', '%PROGRAM%');
            });
        }
        // Filter default berdasarkan prodi
        elseif ($userProdi) {
            $kodeProdi = trim(explode('-', $userProdi)[0]);
            // Log::info('Applying Prodi filter', ['kode_prodi' => $kodeProdi, 'original_prodi' => $userProdi]);
            $query->where('prodi', 'like', "%{$kodeProdi}%");
        }

        // Log SQL query
        // Log::info('Final Query', [
        //     'sql' => $query->toSql(),
        //     'bindings' => $query->getBindings()
        // ]);

        $result = $query->orderBy('tanggal_mulai', 'desc')->get();

        // Log jumlah hasil
        // Log::info('Query Result Count', ['count' => $result->count()]);

        // Log detail hasil
        // if ($result->count() > 0) {
        //     Log::info('Query Results Sample', [
        //         'first_result' => $result->first()->only(['id', 'prodi', 'fakultas', 'standar_akreditasi', 'tanggal_mulai', 'tanggal_selesai'])
        //     ]);
        // }

        return $result;
    }

    public function getProdiListWithJadwal($headerData, $user)
    {
        $activeRole = session('active_role');

        // Log informasi untuk debugging
        // Log::info('getProdiListWithJadwal Request', [
        //     'user_id' => $user->id,
        //     'user_name' => $user->name,
        //     'active_role' => $activeRole,
        //     'header_data' => $headerData ? [
        //         'id' => $headerData->id,
        //         'lembaga_id' => $headerData->lembaga_akreditasi_id,
        //         'jenjang_id' => $headerData->jenjang_id,
        //         'periode' => $headerData->periode_atau_tahun,
        //         'jenjang_nama' => $headerData->jenjang->nama ?? 'undefined'
        //     ] : null
        // ]);

        // KUNCI PERBAIKAN: Ambil daftar prodi yang terdaftar di lembaga akreditasi ini
        $registeredProdiList = [];
        if ($headerData && $headerData->lembaga_akreditasi_id) {
            $registeredProdiList = LembagaAkreditasiDetail::where('lembaga_akreditasi_id', $headerData->lembaga_akreditasi_id)
                ->pluck('prodi')
                ->toArray();

            // Log::info('Registered prodi for lembaga', [
            //     'lembaga_id' => $headerData->lembaga_akreditasi_id,
            //     'registered_prodi' => $registeredProdiList
            // ]);
        }

        // Jika tidak ada prodi yang terdaftar, return empty collection
        if (empty($registeredProdiList)) {
            // Log::info('No registered prodi found for lembaga');
            return collect();
        }

        // EDIT: Build query dengan pivot data untuk role_auditor
        $query = JadwalAmi::with(['timAuditor' => function ($query) {
            $query->withPivot('role_auditor');
        }])
            ->when($headerData, function ($query) use ($headerData) {
                $tahun = substr($headerData->periode_atau_tahun, 0, 4);
                // Log::info('Applying period filter', ['year' => $tahun, 'original_period' => $headerData->periode_atau_tahun]);
                return $query->whereRaw('SUBSTRING(periode, 1, 4) = ?', [$tahun]);
            })
            // TAMBAHAN PERBAIKAN: Filter berdasarkan standar akreditasi
            ->when($headerData?->lembagaAkreditasi, function ($query) use ($headerData) {
                $lembagaAkreditasiNama = $headerData->lembagaAkreditasi->nama;
                // Log::info('Applying lembaga akreditasi filter', ['lembaga_nama' => $lembagaAkreditasiNama]);
                return $query->where('standar_akreditasi', $lembagaAkreditasiNama);
            })
            // PERBAIKAN: Filter hanya prodi yang terdaftar di lembaga akreditasi
            ->where(function ($query) use ($registeredProdiList) {
                foreach ($registeredProdiList as $registeredProdi) {
                    $kodeProdi = trim(explode('-', $registeredProdi)[0]);
                    $query->orWhere('prodi', 'like', "%{$kodeProdi}%");
                }
            })
            ->when($headerData?->jenjang, function ($query) use ($headerData) {
                $jenjangNama = $headerData->jenjang->nama;

                // Log::info('Checking jenjang filter', [
                //     'jenjang_nama' => $jenjangNama,
                //     'jenjang_id' => $headerData->jenjang_id
                // ]);

                // Jika jenjang adalah PPG, PP atau program profesi lainnya
                if (
                    in_array($jenjangNama, ['PPG', 'PP']) ||
                    str_contains(strtolower($jenjangNama), 'profesi')
                ) {

                    // Log::info('Applying profesi filter');
                    return $query->where(function ($q) {
                        $q->where('prodi', 'like', '%Profesi%')
                            ->orWhere('prodi', 'like', '%Program Profesi%')
                            ->orWhere('prodi', 'like', '%PPG%')
                            ->orWhere('prodi', 'like', '%PP)%');
                    });
                }
                // Format standar dengan jenjang dalam kurung (S1, S2, dll)
                else {
                    $jenjangPattern = '%(' . $jenjangNama . ')%';
                    // Log::info('Applying standard jenjang filter', ['pattern' => $jenjangPattern]);
                    return $query->where('prodi', 'like', $jenjangPattern);
                }
            });

        // Cek role dengan manual logging
        $isSuperAdmin = $activeRole === 'Super Admin';
        $isAdminLPM = $activeRole === 'Admin LPM';
        $isAuditor = $activeRole === 'Auditor';
        $isFakultas = $activeRole === 'Fakultas';

        // Apply filters based on role
        if ($isAuditor) {
            // Log::info('Applying Auditor filter for prodi list');
            $query->whereHas('timAuditor', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        // TAMBAHKAN DI SINI - setelah if ($isAuditor)
        elseif ($activeRole === 'Admin PPG') {
            $query->where(function ($q) {
                $q->where('prodi', 'like', '%profesi%')
                    ->orWhere('prodi', 'like', '%ppg%')
                    ->orWhere('prodi', 'like', '%program profesi%')
                    ->orWhere('prodi', 'like', '%PPG%')
                    ->orWhere('prodi', 'like', '%PP%')
                    ->orWhere('prodi', 'like', '%Profesi%')
                    ->orWhere('prodi', 'like', '%Program Profesi%')
                    ->orWhere('prodi', 'like', '%PROGRAM%');
            });
        } elseif ($isFakultas) {
            $userFakultas = $user->fakultas;
            // Log::info('Applying Fakultas filter for prodi list', ['fakultas' => $userFakultas]);
            $query->where('fakultas', $userFakultas);
        } elseif (!$isSuperAdmin && !$isAdminLPM) {
            $userProdi = $user->prodi;
            if ($userProdi) {
                $kodeProdi = trim(explode('-', $userProdi)[0]);
                // Log::info('Applying Prodi filter for prodi list', ['kode_prodi' => $kodeProdi, 'original_prodi' => $userProdi]);
                $query->where('prodi', 'like', "%{$kodeProdi}%");
            }
        } else {
            // Log::info('No filter applied for prodi list - Admin role detected');
        }

        // Log SQL query
        // Log::info('Prodi List Query', [
        //     'sql' => $query->toSql(),
        //     'bindings' => $query->getBindings()
        // ]);

        // Execute query and map results
        $result = $query->get();

        // Log result count
        // Log::info('Prodi List Result Count', ['count' => $result->count()]);

        // EDIT: Map to required format dengan tim_auditor_detail
        $prodiList = $result->map(function ($jadwal) {
            // TAMBAH: Get tim auditor detail dengan roles
            $timAuditorDetail = $this->getTimAuditorDetail($jadwal);

            return [
                'prodi' => $jadwal->prodi,
                'fakultas' => $jadwal->fakultas,
                'tanggal_mulai' => $jadwal->tanggal_mulai,
                'tanggal_selesai' => $jadwal->tanggal_selesai,
                'status' => app(StatusService::class)->getStatus($jadwal),
                'tim_auditor' => $jadwal->timAuditor->pluck('name')->join(', '),
                'tim_auditor_detail' => $timAuditorDetail,
                'has_schedule' => !is_null($jadwal->tanggal_mulai) && !is_null($jadwal->tanggal_selesai)
            ];
        });

        // Log sample of mapped results
        // if ($prodiList->count() > 0) {
        //     Log::info('Prodi List Sample', [
        //         'first_item' => $prodiList->first()
        //     ]);
        // }

        return $prodiList;
    }

    /**
     * FUNGSI BARU: Get detailed tim auditor information with ketua and anggota
     */
    protected function getTimAuditorDetail($jadwal)
    {
        if (!$jadwal || !$jadwal->timAuditor) {
            return [
                'ketua' => null,
                'anggota' => []
            ];
        }

        // Get ketua auditor (role_auditor = 'ketua')
        $ketua = $jadwal->timAuditor
            ->where('pivot.role_auditor', 'ketua')
            ->first();

        // Get anggota auditor (role_auditor = 'anggota')
        $anggota = $jadwal->timAuditor
            ->where('pivot.role_auditor', 'anggota')
            ->pluck('name')
            ->toArray();

        return [
            'ketua' => $ketua ? $ketua->name : null,
            'anggota' => $anggota
        ];
    }

    // Fungsi helper untuk mendeteksi jenjang dari string prodi
    public static function detectJenjangFromProdi($prodiString)
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
            return self::findAvailableProfesiJenjang();
        }

        return null;
    }

    private static function findAvailableProfesiJenjang()
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
