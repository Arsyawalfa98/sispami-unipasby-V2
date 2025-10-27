<?php
// app/Services/PemenuhanDokumen/JadwalService.php
namespace App\Services\PemenuhanDokumen;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class JadwalService
{
    protected $statusService;

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function mapJadwalDetails($detail, $jadwalAmiList)
    {
        // Log untuk debugging
        // Log::info('mapJadwalDetails called', [
        //     'detail_prodi' => $detail['prodi'],
        //     'jadwal_count' => $jadwalAmiList->count()
        // ]);
        
        // Step 1: Pencocokan exact
        $jadwal = $jadwalAmiList->first(function($jadwal) use ($detail) {
            return $jadwal->prodi === $detail['prodi'];
        });
        
        // Step 2: Jika tidak ditemukan, coba pencocokan berdasarkan kode prodi
        if (!$jadwal) {
            $detailKodeProdi = trim(explode('-', $detail['prodi'])[0]);
            
            $jadwal = $jadwalAmiList->first(function($jadwal) use ($detailKodeProdi) {
                $jadwalKodeProdi = trim(explode('-', $jadwal->prodi)[0]);
                return $jadwalKodeProdi === $detailKodeProdi;
            });
            
            // if ($jadwal) {
            //     Log::info('Matched by prodi code', [
            //         'detail_prodi' => $detail['prodi'],
            //         'jadwal_prodi' => $jadwal->prodi,
            //         'kode' => $detailKodeProdi
            //     ]);
            // }
        }
        
        // Step 3: Untuk PPG/Program Profesi, coba pencocokan berdasarkan kata kunci
        if (!$jadwal) {
            $isProfesi = strpos(strtolower($detail['prodi']), 'profesi') !== false || 
                        strpos($detail['prodi'], 'PPG') !== false;
                        
            if ($isProfesi) {
                $jadwal = $jadwalAmiList->first(function($jadwal) {
                    return strpos(strtolower($jadwal->prodi), 'profesi') !== false || 
                           strpos($jadwal->prodi, 'PPG') !== false;
                });
                
                // if ($jadwal) {
                //     Log::info('Matched by profesi keyword', [
                //         'detail_prodi' => $detail['prodi'],
                //         'jadwal_prodi' => $jadwal->prodi
                //     ]);
                // }
            }
        }
        
        // EDIT: Tambahkan tim_auditor_detail di defaultResponse
        $defaultResponse = [
            'prodi' => $detail['prodi'],
            'fakultas' => $detail['fakultas'] ?? null,
            'jadwal' => null,
            'status' => 'Belum ada jadwal',
            'tim_auditor' => '-',
            'tim_auditor_detail' => [
                'ketua' => null,
                'anggota' => []
            ]
        ];
    
        if (!$jadwal) {
            // Log::info('No matching jadwal found', [
            //     'detail_prodi' => $detail['prodi']
            // ]);
            return $defaultResponse;
        }

        // TAMBAH: Get tim auditor detail dengan roles
        $timAuditorDetail = $this->getTimAuditorDetail($jadwal);
    
        // EDIT: Tambahkan tim_auditor_detail di result
        $result = [
            'prodi' => $detail['prodi'],
            'fakultas' => $detail['fakultas'] ?? null,
            'jadwal' => [
                'tanggal_mulai' => $jadwal->tanggal_mulai,
                'tanggal_selesai' => $jadwal->tanggal_selesai
            ],
            'status' => $this->statusService->getStatus($jadwal),
            'tim_auditor' => $jadwal->timAuditor->pluck('name')->join(', ') ?: '-',
            'tim_auditor_detail' => $timAuditorDetail
        ];
        
        // Log::info('Matched jadwal', [
        //     'detail_prodi' => $detail['prodi'],
        //     'jadwal_prodi' => $jadwal->prodi,
        //     'status' => $result['status']
        // ]);
        
        return $result;
    }

    public function mapJadwalDetailsWithLembaga($detail, $jadwalAmiList, $lembagaAkreditasiNama)
    {
        // Step 1: Pencocokan exact dengan tambahan validasi lembaga akreditasi
        $jadwal = $jadwalAmiList->first(function($jadwal) use ($detail, $lembagaAkreditasiNama) {
            return $jadwal->prodi === $detail['prodi'] && $jadwal->standar_akreditasi === $lembagaAkreditasiNama;
        });
        
        // Step 2: Jika tidak ditemukan, coba pencocokan berdasarkan kode prodi
        if (!$jadwal) {
            $detailKodeProdi = trim(explode('-', $detail['prodi'])[0]);
            
            $jadwal = $jadwalAmiList->first(function($jadwal) use ($detailKodeProdi, $lembagaAkreditasiNama) {
                $jadwalKodeProdi = trim(explode('-', $jadwal->prodi)[0]);
                return $jadwalKodeProdi === $detailKodeProdi && $jadwal->standar_akreditasi === $lembagaAkreditasiNama;
            });
        }
        
        // Step 3: Untuk PPG/Program Profesi, coba pencocokan berdasarkan kata kunci
        if (!$jadwal) {
            $isProfesi = strpos(strtolower($detail['prodi']), 'profesi') !== false || 
                        strpos($detail['prodi'], 'PPG') !== false;
                        
            if ($isProfesi) {
                $jadwal = $jadwalAmiList->first(function($jadwal) use ($lembagaAkreditasiNama) {
                    return (strpos(strtolower($jadwal->prodi), 'profesi') !== false || 
                        strpos($jadwal->prodi, 'PPG') !== false) && 
                        $jadwal->standar_akreditasi === $lembagaAkreditasiNama;
                });
            }
        }
        
        // EDIT: Tambahkan tim_auditor_detail di defaultResponse
        $defaultResponse = [
            'prodi' => $detail['prodi'],
            'fakultas' => $detail['fakultas'] ?? null,
            'jadwal' => null,
            'status' => 'Belum ada jadwal',
            'tim_auditor' => '-',
            'tim_auditor_detail' => [
                'ketua' => null,
                'anggota' => []
            ]
        ];

        if (!$jadwal) {
            return $defaultResponse;
        }

        // TAMBAH: Get tim auditor detail dengan roles
        $timAuditorDetail = $this->getTimAuditorDetail($jadwal);

        // EDIT: Tambahkan tim_auditor_detail di result
        $result = [
            'prodi' => $detail['prodi'],
            'fakultas' => $detail['fakultas'] ?? null,
            'jadwal' => [
                'tanggal_mulai' => $jadwal->tanggal_mulai,
                'tanggal_selesai' => $jadwal->tanggal_selesai
            ],
            'status' => $this->statusService->getStatus($jadwal),
            'tim_auditor' => $jadwal->timAuditor->pluck('name')->join(', ') ?: '-',
            'tim_auditor_detail' => $timAuditorDetail
        ];
        
        return $result;
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

    /**
     * FUNGSI BARU: Format tim auditor string with role information
     */
    public function formatTimAuditorString($jadwal)
    {
        $timAuditorDetail = $this->getTimAuditorDetail($jadwal);
        
        $parts = [];
        
        if ($timAuditorDetail['ketua']) {
            $parts[] = $timAuditorDetail['ketua'] . ' (Ketua)';
        }
        
        if (!empty($timAuditorDetail['anggota'])) {
            foreach ($timAuditorDetail['anggota'] as $anggota) {
                $parts[] = $anggota . ' (Anggota)';
            }
        }
        
        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    /**
     * FUNGSI BARU: Format tim auditor untuk dropdown (text only)
     */
    public static function formatTimAuditorForDropdown($timAuditorDetail, $fallbackString = '')
    {
        if (!$timAuditorDetail || (!$timAuditorDetail['ketua'] && empty($timAuditorDetail['anggota']))) {
            return $fallbackString ?: '-';
        }

        $parts = [];
        
        if ($timAuditorDetail['ketua']) {
            $parts[] = $timAuditorDetail['ketua'] . ' (Ketua)';
        }
        
        if (!empty($timAuditorDetail['anggota'])) {
            foreach ($timAuditorDetail['anggota'] as $anggota) {
                $parts[] = $anggota . ' (Anggota)';
            }
        }
        
        return !empty($parts) ? implode(', ', $parts) : ($fallbackString ?: '-');
    }
}