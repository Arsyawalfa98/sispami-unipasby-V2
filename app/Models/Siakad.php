<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Siakad extends Model
{
    protected $connection = 'mitra';

    // Untuk button integrasi (tanpa password)
    public static function checkMitraUser($username)
    {
        return self::getMitraUserData($username);
    }

    // Untuk login form dengan password
    public static function checkMitraUserWithPassword($username, $password)
    {
        return self::getMitraUserData($username, $password);
    }

    // Fungsi utama untuk mendapatkan data dari mitra
    private static function getMitraUserData($username, $password = null)
    {
        $params = [$username];
        if ($password !== null) {
            $params[] = $password;
        }

        // Cek dengan idpegawai
        $idpegawaiQuery = "
            SELECT 
                u.username,
                u.userdesc as nama_user,
                u.email,
                p.idpegawai,
                p.idunit,
                p.nidn,
                CONCAT(un.idunit, ' - ', un.namaunit) as nama_unit,
                CASE 
                    WHEN parent.idunit IS NOT NULL 
                    THEN CONCAT(parent.idunit, ' - ', parent.namaunit) 
                    ELSE NULL 
                END as nama_parent_unit
            FROM gate.sc_user u
            JOIN sdm.ms_pegawai p ON TRIM(LEADING '0' FROM CAST(u.username AS VARCHAR(50))) = CAST(p.idpegawai AS VARCHAR(50))
            LEFT JOIN sdm.ms_unit un ON p.idunit = un.idunit
            LEFT JOIN sdm.ms_unit parent ON un.parentunit = parent.idunit
            WHERE u.username = ?" . ($password !== null ? " AND u.password = ?" : "");

        $userByIdPegawai = DB::connection('mitra')->select($idpegawaiQuery, $params);

        if (!empty($userByIdPegawai)) {
            return $userByIdPegawai[0];
        }

        // Query terpisah untuk NIDN
        $nidnQuery = "
            SELECT 
                u.username,
                u.userdesc as nama_user,
                u.email,
                p.idpegawai,
                p.idunit,
                p.nidn,
                CONCAT(un.idunit, ' - ', un.namaunit) as nama_unit,
                CASE 
                    WHEN parent.idunit IS NOT NULL 
                    THEN CONCAT(parent.idunit, ' - ', parent.namaunit) 
                    ELSE NULL 
                END as nama_parent_unit
            FROM gate.sc_user u
            JOIN sdm.ms_pegawai p ON TRIM(LEADING '0' FROM CAST(u.username AS VARCHAR(50))) = TRIM(LEADING '0' FROM CAST(p.nidn AS VARCHAR(50)))
            LEFT JOIN sdm.ms_unit un ON p.idunit = un.idunit
            LEFT JOIN sdm.ms_unit parent ON un.parentunit = parent.idunit
            WHERE u.username = ?" . ($password !== null ? " AND u.password = ?" : "");

        $userByNidn = DB::connection('mitra')->select($nidnQuery, $params);

        return !empty($userByNidn) ? $userByNidn[0] : null;
    }

    // Fungsi untuk mendapatkan data unit (prodi dan fakultas)
    public static function getUnit()
    {
        return DB::connection('mitra')
            ->select("
                SELECT kodeunit, namaunit, kodeunitparent
                FROM gate.ms_unit
                ORDER BY kodeunit
            ");
    }

    // Fungsi khusus untuk mendapatkan prodi
    public static function getProdi()
    {
        return DB::connection('mitra')
            ->select("
                SELECT a.kodeunit, a.namaunit, a.kodeunitparent,
                       b.kodeunit as fakultas_kode, b.namaunit as fakultas_nama
                FROM gate.ms_unit a
                LEFT JOIN gate.ms_unit b ON b.kodeunit = a.kodeunitparent
                WHERE a.kodeunitparent IS NOT NULL
                ORDER BY a.kodeunit
            ");
    }

    // Fungsi khusus untuk mendapatkan fakultas
    public static function getFakultas()
    {
        return DB::connection('mitra')
            ->select("
                SELECT kodeunit, namaunit
                FROM gate.ms_unit
                WHERE kodeunitparent IS NULL
                ORDER BY kodeunit
            ");
    }

    // Fungsi untuk mendapatkan fakultas berdasarkan prodi
    public static function getFakultasByProdi($kodeProdi)
    {
        return DB::connection('mitra')
            ->select("
                SELECT b.kodeunit, b.namaunit
                FROM gate.ms_unit a
                JOIN gate.ms_unit b ON b.kodeunit = a.kodeunitparent
                WHERE a.kodeunit = ?
            ", [$kodeProdi]);
    }

    public static function searchProdi($search)
    {
        return DB::connection('mitra')
            ->select("
                SELECT a.kodeunit, a.namaunit, a.kodeunitparent,
                    b.kodeunit as fakultas_kode, b.namaunit as fakultas_nama
                FROM gate.ms_unit a
                LEFT JOIN gate.ms_unit b ON b.kodeunit = a.kodeunitparent
                WHERE a.kodeunitparent IS NOT NULL
                AND (
                    LOWER(a.kodeunit) LIKE LOWER(?) 
                    OR LOWER(a.namaunit) LIKE LOWER(?)
                )
                ORDER BY a.kodeunit
                LIMIT 10
            ", ["%$search%", "%$search%"]);
    }

    // Tambahkan method ini di App\Models\Siakad

    public static function searchUsername($search)
    {
        return DB::connection('mitra')
            ->select("
            SELECT 
                u.username,
                u.userdesc as nama_user,
                u.email,
                p.idpegawai,
                p.idunit,
                p.nidn,
                CONCAT(un.kodeunit, ' - ', un.namaunit) as nama_unit,
                CASE 
                    WHEN parent.kodeunit IS NOT NULL 
                    THEN CONCAT(parent.kodeunit, ' - ', parent.namaunit) 
                    ELSE NULL 
                END as nama_parent_unit
            FROM gate.sc_user u
            LEFT JOIN sdm.ms_pegawai p ON TRIM(LEADING '0' FROM CAST(u.username AS VARCHAR(50))) = CAST(p.idpegawai AS VARCHAR(50))
                                    OR TRIM(LEADING '0' FROM CAST(u.username AS VARCHAR(50))) = TRIM(LEADING '0' FROM CAST(p.nidn AS VARCHAR(50)))
            LEFT JOIN sdm.ms_unit un ON p.idunit = un.idunit
            LEFT JOIN sdm.ms_unit parent ON un.parentunit = parent.idunit
            WHERE 
                LOWER(u.username) LIKE LOWER(?) 
                OR LOWER(u.userdesc) LIKE LOWER(?)
            ORDER BY u.username
            LIMIT 10
        ", ["%$search%", "%$search%"]);
    }

    /**
     * Get semua prodi/unit dari user berdasarkan gate.sc_userrole
     * Ini untuk mendukung multi-prodi per user
     *
     * @param int $userId User ID dari gate.sc_user
     * @param string $username Username untuk fallback home base
     * @return array
     */
    public static function getUserProdisFromMitra($userId, $username = null)
    {
        try {
            $allProdis = [];

            // 1. Ambil home base prodi dari sdm.ms_pegawai (sebagai fallback/default)
            if ($username) {
                $homeBase = self::getHomeBaseProdi($username);
                if ($homeBase) {
                    $allProdis[$homeBase->kode_prodi] = $homeBase;
                }
            }

            // 2. Ambil prodi dari role KPR di gate.sc_userrole
            $kprProdis = DB::connection('mitra')
                ->select("
                    SELECT DISTINCT
                        ur.kodeunit as kode_prodi,
                        u.namaunit as nama_prodi,
                        u.kodeunitparent as kode_fakultas,
                        parent.namaunit as nama_fakultas
                    FROM gate.sc_userrole ur
                    LEFT JOIN gate.ms_unit u ON ur.kodeunit = u.kodeunit
                    LEFT JOIN gate.ms_unit parent ON u.kodeunitparent = parent.kodeunit
                    WHERE ur.userid = ?
                    AND ur.koderole = 'KPR'
                    AND u.kodeunitparent IS NOT NULL
                    ORDER BY ur.kodeunit
                ", [$userId]);

            // 3. Merge KPR prodis (override duplicates by kode_prodi)
            foreach ($kprProdis as $prodi) {
                $allProdis[$prodi->kode_prodi] = $prodi;
            }

            // Convert associative array back to indexed array
            return array_values($allProdis);

        } catch (\Exception $e) {
            \Log::error('Error fetching user prodis from mitra: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get home base prodi dari sdm.ms_pegawai
     *
     * @param string $username
     * @return object|null
     */
    private static function getHomeBaseProdi($username)
    {
        try {
            // Coba cari via idpegawai
            $result = DB::connection('mitra')
                ->select("
                    SELECT DISTINCT
                        un.kodeunit as kode_prodi,
                        un.namaunit as nama_prodi,
                        un.kodeunitparent as kode_fakultas,
                        parent.namaunit as nama_fakultas
                    FROM sdm.ms_pegawai p
                    LEFT JOIN sdm.ms_unit un ON p.idunit = un.idunit
                    LEFT JOIN sdm.ms_unit parent ON un.parentunit = parent.idunit
                    WHERE TRIM(LEADING '0' FROM CAST(p.idpegawai AS VARCHAR(50))) = ?
                    AND un.kodeunitparent IS NOT NULL
                    LIMIT 1
                ", [$username]);

            if (!empty($result)) {
                return $result[0];
            }

            // Fallback: coba cari via nidn
            $result = DB::connection('mitra')
                ->select("
                    SELECT DISTINCT
                        un.kodeunit as kode_prodi,
                        un.namaunit as nama_prodi,
                        un.kodeunitparent as kode_fakultas,
                        parent.namaunit as nama_fakultas
                    FROM sdm.ms_pegawai p
                    LEFT JOIN sdm.ms_unit un ON p.idunit = un.idunit
                    LEFT JOIN sdm.ms_unit parent ON un.parentunit = parent.idunit
                    WHERE TRIM(LEADING '0' FROM CAST(p.nidn AS VARCHAR(50))) = ?
                    AND un.kodeunitparent IS NOT NULL
                    LIMIT 1
                ", [$username]);

            return !empty($result) ? $result[0] : null;

        } catch (\Exception $e) {
            \Log::error('Error fetching home base prodi: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user ID dari sistem mitra berdasarkan username
     *
     * @param string $username
     * @return int|null
     */
    public static function getMitraUserId($username)
    {
        try {
            $user = DB::connection('mitra')
                ->select("
                    SELECT userid
                    FROM gate.sc_user
                    WHERE username = ?
                    LIMIT 1
                ", [$username]);

            return !empty($user) ? $user[0]->userid : null;
        } catch (\Exception $e) {
            \Log::error('Error fetching mitra user ID: ' . $e->getMessage());
            return null;
        }
    }

    public static function getKaprodiByKodeUnit($kodeUnit)
    {
        try {
            $kaprodi = DB::connection('mitra')
                ->select("
                    SELECT
                        CONCAT_WS(' ',
                            NULLIF(mp.gelardepan, ''),
                            mp.namadepan,
                            NULLIF(mp.namatengah, ''),
                            NULLIF(mp.namabelakang, ''),
                            NULLIF(mp.gelarbelakang, '')
                        ) as nama_lengkap
                    FROM sdm.ms_pegawai mp
                    JOIN sdm.ms_unit mu ON mp.idunit = mu.idunit
                    JOIN sdm.ms_struktural ms ON mp.idjstruktural = ms.idjstruktural
                    WHERE mu.kodeunit = ?
                    AND (ms.idjabatan LIKE ? OR mp.idjstruktural = REPLACE(?, '.', ''))
                    LIMIT 1
                ", [$kodeUnit, '11201', $kodeUnit]);

                // If first query returns empty, try second condition
            if (empty($kaprodi)) {
                $convertedKodeUnit = str_replace('.', '', $kodeUnit); // Convert F.1 to F1

                $kaprodi = DB::connection('mitra')
                            ->select("
                                SELECT
                                    CONCAT_WS(' ',
                                        NULLIF(mp.gelardepan, ''),
                                        mp.namadepan,
                                        NULLIF(mp.namatengah, ''),
                                        NULLIF(mp.namabelakang, ''),
                                        NULLIF(mp.gelarbelakang, '')
                                    ) as nama_lengkap
                                FROM sdm.ms_pegawai mp
                                WHERE mp.idjstruktural = ?
                                LIMIT 1
                            ", [$convertedKodeUnit]);
            }
            return !empty($kaprodi) ? $kaprodi[0]->nama_lengkap : null;
        } catch (\Exception $e) {
            \Log::error('Error fetching kaprodi: ' . $e->getMessage());
            return null;
        }
    }
}
