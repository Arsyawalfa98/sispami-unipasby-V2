<?php

use App\Models\Prodi;
use Illuminate\Support\Facades\Auth;

if (!function_exists('getActiveProdi')) {
    /**
     * Get active kode prodi dari session dengan fallback ke default prodi
     *
     * @return string|null
     */
    function getActiveProdi()
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Cek session dulu
        $activeKodeProdi = session('active_prodi');

        if ($activeKodeProdi) {
            // Validate bahwa user punya akses ke prodi ini
            if ($user->hasAccessToProdi($activeKodeProdi)) {
                return $activeKodeProdi;
            }
        }

        // Fallback ke default prodi
        $defaultProdi = $user->defaultProdi;
        if ($defaultProdi) {
            // Set session untuk next request
            session(['active_prodi' => $defaultProdi->kode_prodi]);
            return $defaultProdi->kode_prodi;
        }

        // Fallback ke prodi pertama yang ditemukan
        $firstProdi = $user->prodis()->first();
        if ($firstProdi) {
            session(['active_prodi' => $firstProdi->kode_prodi]);
            return $firstProdi->kode_prodi;
        }

        // Backward compatibility: ambil dari kolom lama
        if (!empty($user->prodi)) {
            return $user->prodi;
        }

        return null;
    }
}

if (!function_exists('getActiveProdiData')) {
    /**
     * Get active prodi data (full object)
     *
     * @return Prodi|null
     */
    function getActiveProdiData()
    {
        if (!Auth::check()) {
            return null;
        }

        return Auth::user()->getActiveProdi();
    }
}

if (!function_exists('getActiveNamaProdi')) {
    /**
     * Get active nama prodi
     *
     * @return string|null
     */
    function getActiveNamaProdi()
    {
        $prodi = getActiveProdiData();
        return $prodi ? $prodi->nama_prodi : null;
    }
}

if (!function_exists('getActiveFakultas')) {
    /**
     * Get active fakultas
     *
     * PERBAIKAN: Untuk role Fakultas, ambil langsung dari user->fakultas
     * Untuk role lain, ambil dari prodi data
     *
     * @return string|null
     */
    function getActiveFakultas()
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // PRIORITAS 1: Jika user punya fakultas langsung (untuk role Fakultas)
        if (!empty($user->fakultas)) {
            return $user->fakultas;
        }

        // PRIORITAS 2: Ambil dari prodi data (untuk role Admin Prodi, dll)
        $prodi = getActiveProdiData();
        return $prodi ? $prodi->nama_fakultas : null;
    }
}

if (!function_exists('getAllUserProdi')) {
    /**
     * Get all prodi untuk current user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function getAllUserProdi()
    {
        if (!Auth::check()) {
            return collect([]);
        }

        return Auth::user()->prodis;
    }
}

if (!function_exists('setActiveProdi')) {
    /**
     * Set active prodi di session
     *
     * @param string $kodeProdi
     * @return bool Success status
     */
    function setActiveProdi($kodeProdi)
    {
        if (!Auth::check()) {
            return false;
        }

        // Validate user punya akses
        if (!Auth::user()->hasAccessToProdi($kodeProdi)) {
            return false;
        }

        session(['active_prodi' => $kodeProdi]);
        return true;
    }
}
