<?php

namespace App\Services\MonevKts;

use App\Models\KriteriaDokumen;
use App\Models\PenilaianKriteria;
use App\Models\JadwalAmi;
use App\Models\LembagaAkreditasi;
use App\Repositories\PemenuhanDokumen\KriteriaDokumenRepository;
use App\Repositories\PemenuhanDokumen\JadwalAmiRepository;
use App\Services\PemenuhanDokumen\StatusService;
use App\Services\PemenuhanDokumen\JadwalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class MonevService
{
    protected $statusService;
    protected $jadwalService;
    protected $kriteriaDokumenRepo;
    protected $jadwalAmiRepo;

    public function __construct(
        StatusService $statusService,
        JadwalService $jadwalService,
        KriteriaDokumenRepository $kriteriaDokumenRepo,
        JadwalAmiRepository $jadwalAmiRepo
    ) {
        $this->statusService = $statusService;
        $this->jadwalService = $jadwalService;
        $this->kriteriaDokumenRepo = $kriteriaDokumenRepo;
        $this->jadwalAmiRepo = $jadwalAmiRepo;
    }

    /**
     * UNTUK INDEX - Logic yang sudah benar
     */
    public function getFilteredData($filters = [], $perPage = 10)
    {
        $user = Auth::user();

        //Ambil status_temuan dari filter (default: KETIDAKSESUAIAN)
        $statusTemuan = $filters['status_temuan'] ?? 'KETIDAKSESUAIAN';

        // STEP 1: Ambil jadwal sesuai role
        if (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM')) {
            $jadwalAmiList = $this->jadwalAmiRepo->getActiveJadwal();
        } elseif (Auth::user()->hasActiveRole('Fakultas')) {
            $jadwalAmiList = $this->jadwalAmiRepo->getActiveJadwal();
            $filters['fakultas'] = $user->fakultas;
        } else {
            $jadwalAmiList = $this->jadwalAmiRepo->getActiveJadwal($user->prodi);
        }

        // For auditor, add schedule filters
        if (Auth::user()->hasActiveRole('Auditor')) {
            $jadwalAmiList = $jadwalAmiList->filter(function ($jadwal) use ($user) {
                return $jadwal->timAuditor->pluck('id')->contains($user->id);
            });

            $activeSchedules = $jadwalAmiList->map(function ($jadwal) {
                $jenjang = $this->detectJenjangFromProdi($jadwal->prodi);

                return [
                    'prodi' => $jadwal->prodi,
                    'jenjang' => $jenjang,
                    'lembaga' => $jadwal->standar_akreditasi
                ];
            });

            $filters['active_schedules'] = $activeSchedules;
        }

        // STEP 2: Ambil semua data dari repository
        $allData = $this->kriteriaDokumenRepo->getAllKriteriaDokumenWithDetails($filters);
        $kriteriaDokumenCollection = collect($allData);

        // STEP 3: Batch hitung berdasarkan status_temuan
        $dataPerGrup = $this->batchCalculatePerGrup($kriteriaDokumenCollection, $statusTemuan); // ← CHANGED method name & param

        // STEP 4: Map data dengan info status_temuan
        foreach ($kriteriaDokumenCollection as $item) {
            $item->filtered_details = collect($this->getFilteredDetailsWithInfo($item, $jadwalAmiList, $dataPerGrup)); // ← CHANGED method name

            // STEP 5: Ambil total berdasarkan status_temuan
            $grupKey = $this->generateGrupKey($item);
            $totalInGroup = $dataPerGrup[$grupKey]['total'] ?? 0;

            // Set flag untuk button logic (dynamic property name)
            if ($statusTemuan === 'KETIDAKSESUAIAN') {
                $item->has_kts_in_group = $totalInGroup > 0;
                $item->total_kts_in_group = $totalInGroup;
            } elseif ($statusTemuan === 'TERCAPAI') {
                $item->has_tercapai_in_group = $totalInGroup > 0;
                $item->total_tercapai_in_group = $totalInGroup;
            }
        }

        // STEP 6: Filter sesuai role dan jadwal
        foreach ($kriteriaDokumenCollection as $item) {
            if ($item->filtered_details) {
                $item->filtered_details = $item->filtered_details->filter(function ($detail) {
                    $hasJadwal = !empty($detail['jadwal']);
                    $statusOk = $detail['status'] !== 'Belum ada jadwal';
                    return $hasJadwal && $statusOk;
                });
            }
        }

        // Hapus item yang tidak memiliki detail valid
        $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function ($item) {
            return $item->filtered_details && $item->filtered_details->count() > 0;
        });

        // Filter tambahan untuk auditor dan fakultas
        if (Auth::user()->hasActiveRole('Auditor')) {
            $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function ($item) {
                return $item->filtered_details && $item->filtered_details->contains(function ($detail) {
                    return !empty($detail['jadwal']);
                });
            });
        } elseif (Auth::user()->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function ($item) use ($userFakultas) {
                return $item->filtered_details && $item->filtered_details->contains(function ($detail) use ($userFakultas) {
                    return isset($detail['fakultas']) && $detail['fakultas'] == $userFakultas;
                });
            });
        }

        // Pagination
        $totalAfterFilters = $kriteriaDokumenCollection->count();
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $kriteriaDokumenCollection->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $totalAfterFilters,
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    /**
     * UNTUK SHOW GROUP - Gunakan logic yang sama dengan index
     */
    public function getShowGroupData($lembagaId, $jenjangId, $selectedProdi, $user, $statusTemuan = 'KETIDAKSESUAIAN') // ← ADD parameter
    {
        try {
            // 1. Get header data
            $headerData = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
                ->where('lembaga_akreditasi_id', $lembagaId)
                ->where('jenjang_id', $jenjangId)
                ->first();

            if (!$headerData) {
                throw new \Exception('Data tidak ditemukan');
            }

            // 2. GUNAKAN LOGIC YANG SAMA SEPERTI INDEX untuk mendapatkan jadwal dan prodi list
            $prodiListWithAuditor = $this->getProdiListLikeIndex($lembagaId, $jenjangId, $user);

            // 3. Find current prodi data dari hasil yang sudah berhasil
            $currentProdiData = null;
            if ($selectedProdi) {
                $currentProdiData = collect($prodiListWithAuditor)->firstWhere('prodi', $selectedProdi);
            }

            // 4. Get kriteria dokumen dengan status_temuan jika prodi dipilih
            $kriteriaDokumen = collect();
            if ($selectedProdi) {
                $kriteriaDokumen = $this->getKriteriaWithStatus( // ← CHANGED method name
                    $lembagaId,
                    $jenjangId,
                    $selectedProdi,
                    $headerData->periode_atau_tahun,
                    $statusTemuan // ← ADD parameter
                );
            }

            return [
                'kriteriaDokumen' => $kriteriaDokumen,
                'headerData' => $headerData,
                'prodiList' => $prodiListWithAuditor,
                'currentProdiData' => $currentProdiData ?? [],
                'roleData' => $this->getRoleData($user)
            ];
        } catch (\Exception $e) {
            throw new \Exception('Gagal mengambil data: ' . $e->getMessage());
        }
    }
    private function getKriteriaWithStatus($lembagaId, $jenjangId, $prodi, $periode, $statusTemuan = 'KETIDAKSESUAIAN')
    {
        // Get semua kriteria untuk grup ini
        $kriteriaDokumen = KriteriaDokumen::with(['judulKriteriaDokumen'])
            ->where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->where('periode_atau_tahun', $periode)
            ->get();

        // ⭐ Get penilaian berdasarkan status_temuan (DINAMIS)
        $penilaian = PenilaianKriteria::whereIn('kriteria_dokumen_id', $kriteriaDokumen->pluck('id'))
            ->where('prodi', $prodi)
            ->where('status_temuan', $statusTemuan) // ← Filter dinamis
            ->get()
            ->keyBy('kriteria_dokumen_id');

        // Group by kriteria name
        $groupedData = collect();

        foreach ($kriteriaDokumen as $kriteria) {
            $penilaianItem = $penilaian->get($kriteria->id);

            if ($penilaianItem) {
                // Dynamic property name
                $propertyName = 'penilaian_' . strtolower($statusTemuan);
                $kriteria->$propertyName = $penilaianItem;

                $kriteriaName = $kriteria->judulKriteriaDokumen->nama_kriteria_dokumen ?? 'Kriteria Lainnya';

                if (!$groupedData->has($kriteriaName)) {
                    $groupedData[$kriteriaName] = collect();
                }

                $groupedData[$kriteriaName]->push($kriteria);
            }
        }

        return $groupedData;
    }
    /**
     * KUNCI: Gunakan logic yang sama persis dengan index untuk mendapatkan prodi list dengan tim auditor
     */

    private function getProdiListLikeIndex($lembagaId, $jenjangId, $user)
    {
        // 1. Ambil jadwal dengan cara yang sama seperti index
        if ($user->hasActiveRole('Super Admin') || $user->hasActiveRole('Admin LPM')) {
            $jadwalAmiList = $this->jadwalAmiRepo->getActiveJadwal();
        } elseif ($user->hasActiveRole('Fakultas')) {
            $jadwalAmiList = $this->jadwalAmiRepo->getActiveJadwal();
        } else {
            $jadwalAmiList = $this->jadwalAmiRepo->getActiveJadwal($user->prodi);
        }

        // 2. Filter untuk auditor
        if ($user->hasActiveRole('Auditor')) {
            $jadwalAmiList = $jadwalAmiList->filter(function ($jadwal) use ($user) {
                return $jadwal->timAuditor->pluck('id')->contains($user->id);
            });
        }

        // 3. Create dummy item untuk menggunakan method getFilteredDetailsWithKtsInfo
        $dummyItem = new \stdClass();
        $dummyItem->lembaga_akreditasi_id = $lembagaId;
        $dummyItem->jenjang_id = $jenjangId;

        // Load relasi
        $lembaga = LembagaAkreditasi::with('lembagaAkreditasiDetail')->find($lembagaId);
        $jenjang = \App\Models\Jenjang::find($jenjangId);

        $dummyItem->lembagaAkreditasi = $lembaga;
        $dummyItem->jenjang = $jenjang;
        $dummyItem->periode_atau_tahun = KriteriaDokumen::where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->first()->periode_atau_tahun ?? '2025';

        // 4. Hitung KTS data
        $ktsDataPerGrup = [];
        $grupKey = $this->generateGrupKey($dummyItem);

        // Get all kriteria_dokumen_ids dalam grup ini
        $allKriteriaIds = KriteriaDokumen::where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->pluck('id');

        // Get all prodi dalam grup ini
        $allProdiInGroup = $lembaga->lembagaAkreditasiDetail->pluck('prodi')->toArray();

        // Hitung KTS per prodi
        $ktsPerProdi = PenilaianKriteria::select('prodi', \DB::raw('COUNT(*) as kts_count'))
            ->whereIn('kriteria_dokumen_id', $allKriteriaIds)
            ->whereIn('prodi', $allProdiInGroup)
            ->where('status_temuan', 'KETIDAKSESUAIAN')
            ->groupBy('prodi')
            ->pluck('kts_count', 'prodi')
            ->toArray();

        $ktsDataPerGrup[$grupKey] = [
            'per_prodi' => $ktsPerProdi,
            'total' => array_sum($ktsPerProdi)
        ];

        // 5. Gunakan method yang sama dengan index
        $mappedDetails = $this->getFilteredDetailsWithInfo($dummyItem, $jadwalAmiList, $ktsDataPerGrup);

        // ⚠️ KUNCI PERBAIKAN: TAMBAHKAN FILTER ROLE SEPERTI DI INDEX! ⚠️
        // 6. Filter berdasarkan role (SAMA SEPERTI getFilteredData)
        if (Auth::user()->hasActiveRole('Auditor')) {
            $mappedDetails = $mappedDetails->filter(function ($detail) {
                return !empty($detail['jadwal']);
            });
        } elseif (Auth::user()->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $mappedDetails = $mappedDetails->filter(function ($detail) use ($userFakultas) {
                return isset($detail['fakultas']) && $detail['fakultas'] == $userFakultas;
            });
        } elseif (Auth::user()->hasActiveRole('Admin Prodi')) {
            $userProdi = $user->prodi;
            $kodeProdi = trim(explode('-', $userProdi)[0]);

            $mappedDetails = $mappedDetails->filter(function ($detail) use ($kodeProdi) {
                return str_starts_with($detail['prodi'], $kodeProdi);
            });
        }

        return $mappedDetails->toArray();
    }

    private function getKriteriaWithKts($lembagaId, $jenjangId, $prodi, $periode)
    {
        // Get semua kriteria untuk grup ini
        $kriteriaDokumen = KriteriaDokumen::with(['judulKriteriaDokumen'])
            ->where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->where('periode_atau_tahun', $periode)
            ->get();

        // Get penilaian KTS
        $penilaianKts = PenilaianKriteria::whereIn('kriteria_dokumen_id', $kriteriaDokumen->pluck('id'))
            ->where('prodi', $prodi)
            ->where('status_temuan', 'KETIDAKSESUAIAN')
            ->get()
            ->keyBy('kriteria_dokumen_id');

        // Group by kriteria name, hanya yang ada KTS
        $groupedData = collect();

        foreach ($kriteriaDokumen as $kriteria) {
            $penilaianKtsItem = $penilaianKts->get($kriteria->id);

            if ($penilaianKtsItem) {
                $kriteria->penilaian_kts = $penilaianKtsItem;

                $kriteriaName = $kriteria->judulKriteriaDokumen->nama_kriteria_dokumen ?? 'Kriteria Lainnya';

                if (!$groupedData->has($kriteriaName)) {
                    $groupedData[$kriteriaName] = collect();
                }

                $groupedData[$kriteriaName]->push($kriteria);
            }
        }

        return $groupedData;
    }

    private function getRoleData($user)
    {
        return [
            'is_admin' => $user->hasActiveRole(['Super Admin', 'Admin LPM']),
            'is_auditor' => $user->hasActiveRole('Auditor'),
            'is_fakultas' => $user->hasActiveRole('Fakultas'),
            'is_admin_prodi' => $user->hasActiveRole('Admin Prodi')
        ];
    }

    /**
     * HELPER METHODS - Copy dari yang sudah benar di index
     */
    private function batchCalculatePerGrup($kriteriaDokumenCollection, $statusTemuan = 'KETIDAKSESUAIAN')
    {
        $data = [];

        $groups = $kriteriaDokumenCollection->groupBy(function ($item) {
            return $this->generateGrupKey($item);
        });

        foreach ($groups as $grupKey => $items) {
            $firstItem = $items->first();

            $allKriteriaIds = KriteriaDokumen::where('lembaga_akreditasi_id', $firstItem->lembaga_akreditasi_id)
                ->where('jenjang_id', $firstItem->jenjang_id)
                ->where('periode_atau_tahun', $firstItem->periode_atau_tahun)
                ->pluck('id');

            $allProdiInGroup = [];
            if ($firstItem->lembagaAkreditasi && $firstItem->lembagaAkreditasi->lembagaAkreditasiDetail) {
                $allProdiInGroup = $firstItem->lembagaAkreditasi->lembagaAkreditasiDetail->pluck('prodi')->toArray();
            }

            // ⭐ Query berdasarkan status_temuan (DINAMIS)
            $perProdi = PenilaianKriteria::select('prodi', \DB::raw('COUNT(*) as count'))
                ->whereIn('kriteria_dokumen_id', $allKriteriaIds)
                ->whereIn('prodi', $allProdiInGroup)
                ->where('status_temuan', $statusTemuan) // ← Filter dinamis
                ->groupBy('prodi')
                ->pluck('count', 'prodi')
                ->toArray();

            $total = array_sum($perProdi);

            $data[$grupKey] = [
                'per_prodi' => $perProdi,
                'total' => $total
            ];
        }

        return $data;
    }

    private function generateGrupKey($item)
    {
        return $item->lembaga_akreditasi_id . '_' . $item->jenjang_id . '_' . $item->periode_atau_tahun;
    }

    protected function getFilteredDetailsWithInfo($item, $jadwalAmiList, $ktsDataPerGrup)
    {
        $user = Auth::user();

        $grupKey = $this->generateGrupKey($item);
        $perProdi = $dataPerGrup[$grupKey]['per_prodi'] ?? [];

        $jenjangNama = $item->jenjang->nama ?? '';
        $isProfesiJenjang = (in_array($jenjangNama, ['PPG', 'PP']) ||
            str_contains(strtolower($jenjangNama), 'profesi'));

        $lembagaAkreditasiNama = $item->lembagaAkreditasi->nama ?? '';

        $registeredProdiList = [];
        if ($item->lembagaAkreditasi && $item->lembagaAkreditasi->lembagaAkreditasiDetail) {
            $registeredProdiList = $item->lembagaAkreditasi->lembagaAkreditasiDetail
                ->pluck('prodi')->toArray();
        }

        // Temukan jadwal yang sesuai dengan jenjang ini
        $relevantJadwals = collect();

        if ($isProfesiJenjang) {
            $relevantJadwals = $jadwalAmiList->filter(function ($jadwal) use ($lembagaAkreditasiNama, $registeredProdiList) {
                if ($jadwal->standar_akreditasi !== $lembagaAkreditasiNama) {
                    return false;
                }

                $isProdiRegistered = false;
                foreach ($registeredProdiList as $registeredProdi) {
                    $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                    $registeredKode = trim(explode('-', $registeredProdi)[0]);

                    if ($jadwalKode === $registeredKode) {
                        $isProdiRegistered = true;
                        break;
                    }
                }

                return $isProdiRegistered && (
                    str_contains(strtolower($jadwal->prodi), 'profesi') ||
                    str_contains($jadwal->prodi, 'PPG') ||
                    str_contains($jadwal->prodi, 'Program Profesi')
                );
            });
        } else {
            $relevantJadwals = $jadwalAmiList->filter(function ($jadwal) use ($jenjangNama, $lembagaAkreditasiNama, $registeredProdiList) {
                if ($jadwal->standar_akreditasi !== $lembagaAkreditasiNama) {
                    return false;
                }

                $isProdiRegistered = false;
                foreach ($registeredProdiList as $registeredProdi) {
                    $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                    $registeredKode = trim(explode('-', $registeredProdi)[0]);

                    if ($jadwalKode === $registeredKode) {
                        $isProdiRegistered = true;
                        break;
                    }
                }

                return $isProdiRegistered && str_contains($jadwal->prodi, "({$jenjangNama})");
            });
        }

        // Filter detail berdasarkan jenjang dan jadwal yang tersedia
        $details = $item->lembagaAkreditasi->lembagaAkreditasiDetail
            ->filter(function ($detail) use ($item, $isProfesiJenjang, $relevantJadwals) {
                $jenjangNama = $item->jenjang->nama ?? '';

                if ($isProfesiJenjang) {
                    if (
                        str_contains(strtolower($detail['prodi']), 'profesi') ||
                        str_contains($detail['prodi'], 'PPG') ||
                        str_contains($detail['prodi'], 'Program Profesi')
                    ) {
                        return true;
                    }

                    if ($relevantJadwals->isNotEmpty()) {
                        $detailKode = trim(explode('-', $detail['prodi'])[0]);

                        foreach ($relevantJadwals as $jadwal) {
                            $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                            if ($jadwalKode === $detailKode) {
                                $detailNama = strtolower($detail['prodi']);
                                $jadwalNama = strtolower($jadwal->prodi);

                                if (str_contains($detailNama, 'profesi') && str_contains($jadwalNama, 'profesi')) {
                                    $detailKeywords = $this->extractProfesiKeywords($detail['prodi']);
                                    $jadwalKeywords = $this->extractProfesiKeywords($jadwal->prodi);

                                    if (!empty(array_intersect($detailKeywords, $jadwalKeywords))) {
                                        return true;
                                    }
                                } else {
                                    return true;
                                }
                            }
                        }
                    }

                    return false;
                } else {
                    return str_contains($detail['prodi'], "({$jenjangNama})");
                }
            });

        // Map details ke format yang diharapkan dengan info KTS
        $mappedDetails = $details->map(function ($detail) use ($jadwalAmiList, $lembagaAkreditasiNama, $perProdi) {
            $filteredJadwalList = $jadwalAmiList->filter(function ($jadwal) use ($lembagaAkreditasiNama, $detail) {
                if ($jadwal->standar_akreditasi !== $lembagaAkreditasiNama) {
                    return false;
                }

                $detailKode = trim(explode('-', $detail['prodi'])[0]);
                $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);

                return $jadwalKode === $detailKode;
            });

            $mappedDetail = $this->jadwalService->mapJadwalDetails($detail, $filteredJadwalList);

            // Tambah data dengan nama generic (bukan kts specific)
            $jumlah = $perProdi[$detail['prodi']] ?? 0; // ← CHANGED
            $mappedDetail['jumlah_temuan'] = $jumlah; // ← CHANGED key name
            $mappedDetail['has_temuan'] = $jumlah > 0; // ← CHANGED key name

            return $mappedDetail;
        });

        // Apply filter based on roles
        if (Auth::user()->hasActiveRole('Auditor')) {
            $mappedDetails = $mappedDetails->filter(function ($detail) {
                return !empty($detail['jadwal']);
            });
        } elseif (Auth::user()->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $mappedDetails = $mappedDetails->filter(function ($detail) use ($userFakultas) {
                return isset($detail['fakultas']) && $detail['fakultas'] == $userFakultas;
            });
        }

        return $mappedDetails;
    }

    private function extractProfesiKeywords($prodiString)
    {
        $keywords = [];
        $lowercaseProdi = strtolower($prodiString);

        if (str_contains($lowercaseProdi, 'profesi')) {
            $parts = explode('profesi', $lowercaseProdi);
            if (count($parts) > 1) {
                $afterProfesi = trim($parts[1]);
                $words = explode(' ', $afterProfesi);
                foreach ($words as $word) {
                    $word = trim($word, '(),.-');
                    if (strlen($word) > 2) {
                        $keywords[] = $word;
                    }
                }
            }
        }

        if (str_contains($lowercaseProdi, 'bidan')) {
            $keywords[] = 'bidan';
        }
        if (str_contains($lowercaseProdi, 'guru')) {
            $keywords[] = 'guru';
        }
        if (str_contains($lowercaseProdi, 'bahasa')) {
            $keywords[] = 'bahasa';
        }

        return array_unique($keywords);
    }

    private function detectJenjangFromProdi($prodiString)
    {
        if (empty($prodiString)) {
            return null;
        }

        if (preg_match('/\((S[0-9]+|D[0-9]+)\)/', $prodiString, $matches)) {
            return trim($matches[1]);
        }

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

    private function findAvailableProfesiJenjang()
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
