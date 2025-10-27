<?php

namespace App\Traits;

use App\Models\LembagaAkreditasiDetail;
use App\Models\KriteriaDokumen;
use Illuminate\Support\Facades\DB;

/**
 * Trait untuk validasi registrasi prodi dalam sistem Pemenuhan Dokumen
 *
 * Trait ini menyediakan helper methods untuk memvalidasi apakah:
 * - Prodi sudah terdaftar di lembaga_akreditasi_detail
 * - Kriteria dokumen tersedia untuk kombinasi prodi + standar akreditasi
 *
 * Usage: use ValidatesProdiRegistration;
 */
trait ValidatesProdiRegistration
{
    /**
     * Check apakah prodi sudah terdaftar di lembaga_akreditasi_detail
     * untuk standar akreditasi tertentu
     *
     * @param string $prodi Nama lengkap prodi
     * @param string $standarAkreditasi Nama lembaga akreditasi (LAMKES, LAMDIK, dll)
     * @return bool
     */
    protected function isProdiRegistered($prodi, $standarAkreditasi)
    {
        if (empty($prodi) || empty($standarAkreditasi)) {
            return false;
        }

        return LembagaAkreditasiDetail::whereHas('lembagaAkreditasi', function($q) use ($standarAkreditasi) {
            $q->where('nama', $standarAkreditasi);
        })
        ->where('prodi', $prodi)
        ->exists();
    }

    /**
     * Check apakah kriteria dokumen tersedia untuk prodi + standar akreditasi
     *
     * @param string $prodi Nama lengkap prodi
     * @param string $standarAkreditasi Nama lembaga akreditasi
     * @return bool
     */
    protected function hasKriteriaDokumen($prodi, $standarAkreditasi)
    {
        if (empty($prodi) || empty($standarAkreditasi)) {
            return false;
        }

        // Detect jenjang dari prodi
        $jenjangList = $this->detectJenjangFromProdi($prodi);

        if (empty($jenjangList)) {
            return false;
        }

        // Check apakah kriteria exists untuk salah satu jenjang
        foreach ($jenjangList as $jenjangName) {
            $exists = KriteriaDokumen::whereHas('lembagaAkreditasi', function($q) use ($standarAkreditasi) {
                $q->where('nama', $standarAkreditasi);
            })
            ->whereHas('jenjang', function($q) use ($jenjangName) {
                $q->where('nama', $jenjangName);
            })
            ->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function($q) use ($prodi) {
                $q->where('prodi', $prodi);
            })
            ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi lengkap: prodi terdaftar + kriteria tersedia
     *
     * @param string $prodi Nama lengkap prodi
     * @param string $standarAkreditasi Nama lembaga akreditasi
     * @return array ['valid' => bool, 'message' => string, 'missing' => array]
     */
    protected function validateProdiForJadwal($prodi, $standarAkreditasi)
    {
        $result = [
            'valid' => true,
            'message' => 'Prodi valid dan kriteria dokumen tersedia',
            'missing' => []
        ];

        // Check 1: Apakah prodi terdaftar?
        if (!$this->isProdiRegistered($prodi, $standarAkreditasi)) {
            $result['valid'] = false;
            $result['missing'][] = 'prodi_registration';
            $result['message'] = "Prodi '{$prodi}' belum terdaftar di lembaga akreditasi '{$standarAkreditasi}'. Silakan tambahkan di Master Data > Lembaga Akreditasi Detail.";
            return $result;
        }

        // Check 2: Apakah kriteria dokumen tersedia?
        if (!$this->hasKriteriaDokumen($prodi, $standarAkreditasi)) {
            $result['valid'] = false;
            $result['missing'][] = 'kriteria_dokumen';

            // Cek apakah kriteria tidak ada atau prodi tidak terdaftar di kriteria
            $jenjangList = $this->detectJenjangFromProdi($prodi);
            $kriteriaExists = KriteriaDokumen::whereHas('lembagaAkreditasi', function($q) use ($standarAkreditasi) {
                $q->where('nama', $standarAkreditasi);
            })
            ->whereHas('jenjang', function($q) use ($jenjangList) {
                $q->whereIn('nama', $jenjangList);
            })
            ->exists();

            if (!$kriteriaExists) {
                $result['message'] = "Kriteria dokumen untuk '{$standarAkreditasi}' dengan jenjang " . implode('/', $jenjangList) . " belum tersedia. Silakan tambahkan di Master Data > Kriteria Dokumen.";
            } else {
                $result['message'] = "Prodi '{$prodi}' sudah terdaftar tetapi belum dikaitkan dengan kriteria dokumen. Pastikan prodi sudah terdaftar di lembaga akreditasi detail yang sesuai.";
            }

            return $result;
        }

        return $result;
    }

    /**
     * Get all prodi yang belum terdaftar di lembaga akreditasi tertentu
     *
     * @param string $standarAkreditasi Nama lembaga akreditasi
     * @return array Array of prodi names
     */
    protected function getUnregisteredProdi($standarAkreditasi)
    {
        // Ambil semua prodi dari jadwal_ami dengan standar akreditasi ini
        $prodiInJadwal = DB::table('jadwal_ami')
            ->where('standar_akreditasi', $standarAkreditasi)
            ->distinct()
            ->pluck('prodi')
            ->toArray();

        // Ambil prodi yang sudah terdaftar
        $registeredProdi = LembagaAkreditasiDetail::whereHas('lembagaAkreditasi', function($q) use ($standarAkreditasi) {
            $q->where('nama', $standarAkreditasi);
        })
        ->pluck('prodi')
        ->toArray();

        // Return prodi yang belum terdaftar
        return array_diff($prodiInJadwal, $registeredProdi);
    }

    /**
     * Detect jenjang dari nama prodi
     *
     * @param string $prodiString Nama lengkap prodi
     * @return array Array of jenjang names
     */
    protected function detectJenjangFromProdi($prodiString)
    {
        if (empty($prodiString)) {
            return [];
        }

        $prodiLower = strtolower($prodiString);

        // Check if profesi/PPG prodi
        $isProfesi = strpos($prodiLower, 'profesi') !== false
                  || strpos($prodiLower, 'ppg') !== false
                  || strpos($prodiLower, 'program profesi') !== false
                  || strpos($prodiLower, 'pp') !== false;

        if ($isProfesi) {
            // Return all profesi jenjang variants
            return ['PROFESI', 'PPG', 'Program Profesi', 'PP'];
        }

        // Detect standard jenjang
        if (strpos($prodiLower, 's3') !== false || strpos($prodiLower, 'doktor') !== false) {
            return ['S3'];
        }
        if (strpos($prodiLower, 's2') !== false || strpos($prodiLower, 'magister') !== false) {
            return ['S2'];
        }
        if (strpos($prodiLower, 's1') !== false || strpos($prodiLower, 'sarjana') !== false) {
            return ['S1'];
        }
        if (strpos($prodiLower, 'd4') !== false || strpos($prodiLower, 'sarjana terapan') !== false) {
            return ['D4'];
        }
        if (strpos($prodiLower, 'd3') !== false || strpos($prodiLower, 'diploma') !== false) {
            return ['D3'];
        }

        return [];
    }

    /**
     * Format validation message untuk user
     *
     * @param array $validation Result dari validateProdiForJadwal
     * @return string HTML formatted message
     */
    protected function formatValidationMessage($validation)
    {
        if ($validation['valid']) {
            return '<span class="text-success">✓ ' . $validation['message'] . '</span>';
        }

        $message = '<span class="text-danger">✗ ' . $validation['message'] . '</span>';

        if (in_array('prodi_registration', $validation['missing'])) {
            $message .= '<br><small>Langkah: Master Data → Lembaga Akreditasi Detail → Tambah Prodi</small>';
        } elseif (in_array('kriteria_dokumen', $validation['missing'])) {
            $message .= '<br><small>Langkah: Master Data → Kriteria Dokumen → Tambah/Edit Kriteria</small>';
        }

        return $message;
    }
}
