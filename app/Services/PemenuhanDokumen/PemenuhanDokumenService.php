<?php
// app/Services/PemenuhanDokumen/PemenuhanDokumenService.php
namespace App\Services\PemenuhanDokumen;

use App\Repositories\PemenuhanDokumen\KriteriaDokumenRepository;
use App\Repositories\PemenuhanDokumen\KriteriaDokumenRepositoryDebug; // DEBUG: Tambahan untuk debugging
use App\Repositories\PemenuhanDokumen\JadwalAmiRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PemenuhanDokumenService
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

    public function getFilteredData($filters = [])
    {
        // The repository's getKriteriaDokumenQuery already handles role-based filtering
        // and the initial filters.
        return $this->kriteriaDokumenRepo->getKriteriaDokumenQuery($filters);
    }

    public function getFilteredDetails($item, $jadwalAmiList)
    {
        $user = Auth::user();

        // Periksa jenjang yang diproses
        $jenjangNama = $item->jenjang->nama ?? '';
        $isProfesiJenjang = (in_array($jenjangNama, ['PPG', 'PP']) ||
            str_contains(strtolower($jenjangNama), 'profesi'));
        
        // Dapatkan nama lembaga akreditasi untuk validasi
        $lembagaAkreditasiNama = $item->lembagaAkreditasi->nama ?? '';
        
        // Dapatkan daftar prodi yang terdaftar pada lembaga akreditasi ini
        $registeredProdiList = [];
        if ($item->lembagaAkreditasi && $item->lembagaAkreditasi->lembagaAkreditasiDetail) {
            $registeredProdiList = $item->lembagaAkreditasi->lembagaAkreditasiDetail
                ->pluck('prodi')->toArray();
        }

        // Temukan jadwal yang sesuai dengan jenjang ini
        $relevantJadwals = collect();

        if ($isProfesiJenjang) {
            // Untuk jenjang profesi, cari jadwal dengan kata kunci profesi atau PPG
            // DAN dengan standar akreditasi yang sama
            // DAN prodi harus terdaftar pada lembaga akreditasi ini
            $relevantJadwals = $jadwalAmiList->filter(function ($jadwal) use ($lembagaAkreditasiNama, $registeredProdiList) {
                // Verifikasi bahwa standar akreditasi cocok
                if ($jadwal->standar_akreditasi !== $lembagaAkreditasiNama) {
                    return false;
                }
                
                // Verifikasi prodi harus terdaftar pada lembaga ini
                $isProdiRegistered = false;
                foreach ($registeredProdiList as $registeredProdi) {
                    // Pencocokan kode prodi
                    $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                    $registeredKode = trim(explode('-', $registeredProdi)[0]);
                    
                    if ($jadwalKode === $registeredKode) {
                        $isProdiRegistered = true;
                        break;
                    }
                }
                
                // Hanya ambil jika prodi terdaftar DAN mengandung keyword profesi
                return $isProdiRegistered && (
                    str_contains(strtolower($jadwal->prodi), 'profesi') ||
                    str_contains($jadwal->prodi, 'PPG') ||
                    str_contains($jadwal->prodi, 'Program Profesi')
                );
            });
        } else {
            // Untuk jenjang lain (S1, S2, dll), cari jadwal dengan format (JenjangNama)
            // DAN dengan standar akreditasi yang sama
            // DAN prodi harus terdaftar pada lembaga akreditasi ini
            $relevantJadwals = $jadwalAmiList->filter(function ($jadwal) use ($jenjangNama, $lembagaAkreditasiNama, $registeredProdiList) {
                // Verifikasi bahwa standar akreditasi cocok
                if ($jadwal->standar_akreditasi !== $lembagaAkreditasiNama) {
                    return false;
                }
                
                // Verifikasi prodi harus terdaftar pada lembaga ini
                $isProdiRegistered = false;
                foreach ($registeredProdiList as $registeredProdi) {
                    // Pencocokan kode prodi
                    $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                    $registeredKode = trim(explode('-', $registeredProdi)[0]);
                    
                    if ($jadwalKode === $registeredKode) {
                        $isProdiRegistered = true;
                        break;
                    }
                }
                
                // Hanya ambil jika prodi terdaftar DAN mengandung jenjang yang sesuai
                return $isProdiRegistered && str_contains($jadwal->prodi, "({$jenjangNama})");
            });
        }

        // Filter detail berdasarkan jenjang dan jadwal yang tersedia
        $details = $item->lembagaAkreditasi->lembagaAkreditasiDetail
            ->filter(function ($detail) use ($item, $isProfesiJenjang, $relevantJadwals) {
                $jenjangNama = $item->jenjang->nama ?? '';

                // Jika ini adalah jenjang PPG/Profesi
                if ($isProfesiJenjang) {
                    // 1. Jika detail sendiri mengandung kata profesi/PPG, gunakan
                    if (
                        str_contains(strtolower($detail['prodi']), 'profesi') ||
                        str_contains($detail['prodi'], 'PPG') ||
                        str_contains($detail['prodi'], 'Program Profesi')
                    ) {
                        return true;
                    }

                    // 2. Hanya ambil detail yang benar-benar memiliki jadwal yang cocok
                    if ($relevantJadwals->isNotEmpty()) {
                        $detailKode = trim(explode('-', $detail['prodi'])[0]);

                        // Cek apakah ada jadwal yang cocok PERSIS dengan detail ini
                        foreach ($relevantJadwals as $jadwal) {
                            $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                            if ($jadwalKode === $detailKode) {
                                // Pastikan nama prodi juga mirip, bukan hanya kode
                                $detailNama = strtolower($detail['prodi']);
                                $jadwalNama = strtolower($jadwal->prodi);
                                
                                // Jika keduanya adalah profesi, harus ada kecocokan kata kunci yang spesifik
                                if (str_contains($detailNama, 'profesi') && str_contains($jadwalNama, 'profesi')) {
                                    // Ekstrak kata kunci spesifik setelah "profesi"
                                    $detailKeywords = $this->extractProfesiKeywords($detail['prodi']);
                                    $jadwalKeywords = $this->extractProfesiKeywords($jadwal->prodi);
                                    
                                    // Harus ada kecocokan kata kunci
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
                }
                // Format standar dengan jenjang dalam kurung (S1, S2, dll)
                else {
                    return str_contains($detail['prodi'], "({$jenjangNama})");
                }
            });

        // Map details ke format yang diharapkan
        $mappedDetails = $details->map(function ($detail) use ($jadwalAmiList, $lembagaAkreditasiNama) {
            // Filter jadwal yang memenuhi syarat lembaga akreditasi dan prodi terdaftar
            $filteredJadwalList = $jadwalAmiList->filter(function ($jadwal) use ($lembagaAkreditasiNama, $detail) {
                // Verifikasi lembaga akreditasi
                if ($jadwal->standar_akreditasi !== $lembagaAkreditasiNama) {
                    return false;
                }
                
                // Pencocokan kode prodi
                $detailKode = trim(explode('-', $detail['prodi'])[0]);
                $jadwalKode = trim(explode('-', $jadwal->prodi)[0]);
                
                return $jadwalKode === $detailKode;
            });

            // Default mapping untuk case normal dengan jadwal yang sudah difilter
            return $this->jadwalService->mapJadwalDetails($detail, $filteredJadwalList);
        });

        // Apply filter based on roles
        if (Auth::user()->hasActiveRole('Auditor')) {
            // Filter hanya detail yang memiliki jadwal
            $mappedDetails = $mappedDetails->filter(function ($detail) {
                return !empty($detail['jadwal']);
            });
        }
        // Filter untuk role Fakultas - hanya tampilkan detail dengan fakultas yang sesuai
        elseif (Auth::user()->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $mappedDetails = $mappedDetails->filter(function ($detail) use ($userFakultas) {
                return isset($detail['fakultas']) && $detail['fakultas'] == $userFakultas;
            });
        }
        return $mappedDetails;
    }

    // Helper method untuk ekstrak kata kunci profesi
    private function extractProfesiKeywords($prodiString)
    {
        $keywords = [];
        $lowercaseProdi = strtolower($prodiString);
        
        // Ekstrak kata kunci setelah "profesi"
        if (str_contains($lowercaseProdi, 'profesi')) {
            $parts = explode('profesi', $lowercaseProdi);
            if (count($parts) > 1) {
                $afterProfesi = trim($parts[1]);
                $words = explode(' ', $afterProfesi);
                foreach ($words as $word) {
                    $word = trim($word, '(),.-');
                    if (strlen($word) > 2) { // Hanya ambil kata yang cukup panjang
                        $keywords[] = $word;
                    }
                }
            }
        }
        
        // Tambahkan juga kata kunci khusus
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

public function getShowGroupData($lembagaId, $jenjangId, $selectedProdi, $user)
{
    // PRESERVE: Original header data logic
    $headerData = $this->kriteriaDokumenRepo->getHeaderData($lembagaId, $jenjangId);

    // PRESERVE: Original prodi list logic
    $prodiList = $this->jadwalAmiRepo->getProdiListWithJadwal($headerData, $user);

    // PRESERVE: Original kriteria dokumen logic
    $kriteriaDokumen = $selectedProdi ?
        $this->kriteriaDokumenRepo->getKriteriaDokumenWithCapaian($lembagaId, $jenjangId, $selectedProdi) :
        collect();

    // NEW: Pre-load penilaian data to eliminate N+1 in view
    $penilaianData = $this->preloadPenilaianData($kriteriaDokumen, $selectedProdi);
    
    // NEW: Pre-calculate jadwal data that was computed in view
    $jadwalData = $this->preloadJadwalData($selectedProdi, $headerData);
    
    // NEW: Get current prodi data from prodi list
    $currentProdiData = $selectedProdi 
        ? $prodiList->firstWhere('prodi', $selectedProdi) 
        : [];

    // NEW: Pre-calculate status dokumen (moved from controller)
    $statusDokumen = $this->calculateStatusDokumen($kriteriaDokumen);
    
    // NEW: Pre-calculate penilaian status (moved from controller) 
    $penilaianStatus = $this->getPenilaianStatus($selectedProdi, $lembagaId, $jenjangId);
    
    // NEW: Pre-calculate role data (centralize from view)
    $roleData = $this->getRoleData($user);

    // NEW: Pre-calculate all criteria met status (from view)
    $allCriteriaMet = $this->checkAllCriteriaMet($kriteriaDokumen);

    // PRESERVE original return structure + add new pre-calculated data
    return [
        'kriteriaDokumen' => $kriteriaDokumen,
        'headerData' => $headerData,
        'prodiList' => $prodiList,
        // New pre-calculated data to eliminate view queries
        'penilaianData' => $penilaianData,
        'jadwalData' => $jadwalData,
        'currentProdiData' => $currentProdiData,
        'statusDokumen' => $statusDokumen,
        'penilaianStatus' => $penilaianStatus,
        'roleData' => $roleData,
        'allCriteriaMet' => $allCriteriaMet
    ];
}

private function preloadPenilaianData($kriteriaDokumen, $selectedProdi)
{
    if (!$selectedProdi || $kriteriaDokumen->isEmpty()) {
        return collect();
    }

    // Get all kriteria IDs from flattened collection
    $kriteriaIds = $kriteriaDokumen->flatten()->pluck('id');
    
    // Single query to get all penilaian data - eliminates N+1 in view
    return \App\Models\PenilaianKriteria::whereIn('kriteria_dokumen_id', $kriteriaIds)
        ->where('prodi', $selectedProdi)
        ->get()
        ->keyBy('kriteria_dokumen_id');
}

private function preloadJadwalData($selectedProdi, $headerData)
{
    if (!$selectedProdi) {
        return [
            'jadwal' => null,
            'active' => false,
            'expired' => false
        ];
    }

    // PRESERVE: Exact jadwal query logic from original view
    $jadwalAmi = \App\Models\JadwalAmi::where('prodi', 'like', "%{$selectedProdi}%")
        ->whereRaw('LEFT(periode, 4) = ?', [
            $headerData->periode_atau_tahun ?? date('Y')
        ])
        ->first();

    $jadwalActive = false;
    $jadwalExpired = false;

    if ($jadwalAmi) {
        $jadwalMulai = \Carbon\Carbon::parse($jadwalAmi->tanggal_mulai);
        $jadwalSelesai = \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai);
        $now = \Carbon\Carbon::now();

        $jadwalActive = $now->between($jadwalMulai, $jadwalSelesai);
        $jadwalExpired = $now->greaterThan($jadwalSelesai);
    }

    return [
        'jadwal' => $jadwalAmi,
        'active' => $jadwalActive,
        'expired' => $jadwalExpired
    ];
}

private function calculateStatusDokumen($kriteriaDokumen)
{
    // PRESERVE: Exact logic from original controller
    $totalKebutuhanSum = 0;
    $totalCapaianSum = 0;
    $statusDokumen = false;
    
    foreach ($kriteriaDokumen as $categoryName => $categoryCollection) {
        if (empty($categoryName)) continue;
        
        foreach ($categoryCollection as $kriteriaDokumenItem) {
            $totalKebutuhanSum += $kriteriaDokumenItem->total_kebutuhan;
            $totalCapaianSum += $kriteriaDokumenItem->capaian_dokumen;
            
            // Original controller logic
            $statusDokumen = ($kriteriaDokumenItem->capaian_dokumen != $kriteriaDokumenItem->total_kebutuhan) ? false : true;
        }
    }
    
    return $statusDokumen;
}

private function getPenilaianStatus($selectedProdi, $lembagaId, $jenjangId)
{
    if (!$selectedProdi) {
        return null;
    }

    // PRESERVE: Exact query logic from original controller
    $penilaian = \App\Models\PenilaianKriteria::where('prodi', $selectedProdi)
        ->whereHas('kriteriaDokumen', function ($query) use ($lembagaId, $jenjangId) {
            $query->where('lembaga_akreditasi_id', $lembagaId)
                ->where('jenjang_id', $jenjangId);
        })
        ->first();

    return $penilaian ? $penilaian->status : \App\Models\PenilaianKriteria::STATUS_DRAFT;
}

private function getRoleData($user)
{
    // PRESERVE: Exact role checking logic from original view
    return [
        'isAdmin' => $user->hasActiveRole('Admin Prodi'),
        'isSuperAdmin' => $user->hasActiveRole('Super Admin'),
        'isAdminLPM' => $user->hasActiveRole('Admin LPM'),
        'isAuditor' => $user->hasActiveRole('Auditor'),
        'adminWithFullAccess' => $user->hasActiveRole('Super Admin') || $user->hasActiveRole('Admin LPM')
    ];
}

private function checkAllCriteriaMet($kriteriaDokumen)
{
    // PRESERVE: Exact logic from original view
    $allCriteriaMet = true;
    foreach ($kriteriaDokumen as $items) {
        foreach ($items as $item) {
            if ($item->capaian_dokumen < $item->total_kebutuhan) {
                $allCriteriaMet = false;
                break 2;
            }
        }
    }
    return $allCriteriaMet;
}


    // Fungsi helper untuk mendeteksi jenjang dari string prodi
    private function detectJenjangFromProdi($prodiString)
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
        'profesi', 'ppg', 'program profesi', 'PPG', 'PP', 'Profesi', 'Program Profesi', 'PROGRAM'
    ];
    
    foreach ($profesiPatterns as $pattern) {
        if (strpos($lowercaseProdi, strtolower($pattern)) !== false || 
            strpos($prodiString, $pattern) !== false) {
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
        'PROFESI', 'PP', 'PPG', 'PROGRAM', 'Program Profesi', 'Profesi'
    ];
    
    foreach ($possibleJenjangNames as $jenjangName) {
        $exists = \DB::table('jenjang')->where('nama', $jenjangName)->exists();
        if ($exists) {
            return $jenjangName;
        }
    }
    
    // Fallback dengan LIKE
    $fallbackJenjang = \DB::table('jenjang')
        ->where(function($query) {
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