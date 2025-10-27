<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserRoleController extends Controller
{
    /**
     * Switch user's active role
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchRole(Request $request)
    {
        $role = $request->role;
        $kodeProdi = $request->prodi; // Tambahan untuk multi-prodi

        $user = Auth::user();

        // Validasi bahwa user memiliki role yang diminta
        $userRoles = $user->roles->pluck('name')->toArray();

        if (!in_array($role, $userRoles)) {
            return redirect()->back()->with('error', 'Role tidak valid');
        }

        // Jika ada prodi parameter, validasi dan set
        if ($kodeProdi) {
            // Validasi user punya akses ke prodi ini
            if (!$user->hasAccessToProdi($kodeProdi)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke prodi tersebut');
            }

            // Set both role and prodi
            session([
                'active_role' => $role,
                'active_prodi' => $kodeProdi
            ]);

            $prodiData = $user->prodis()->where('kode_prodi', $kodeProdi)->first();
            $namaProdi = $prodiData ? $prodiData->nama_prodi : $kodeProdi;

            Log::info("User {$user->username} switched to role: {$role}, prodi: {$kodeProdi}");

            return redirect()->route('home')->with('success', "Beralih ke {$role} ({$namaProdi})");
        }

        // Jika tidak ada prodi (backward compatibility untuk role-only)
        session(['active_role' => $role]);

        return redirect()->route('home')->with('success', "Role diubah ke $role");
    }

    /**
     * Switch prodi only (keep same role)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchProdi(Request $request)
    {
        $kodeProdi = $request->prodi;
        $user = Auth::user();

        // Validasi user punya akses ke prodi ini
        if (!$user->hasAccessToProdi($kodeProdi)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke prodi tersebut');
        }

        // Set active prodi
        session(['active_prodi' => $kodeProdi]);

        $prodiData = $user->prodis()->where('kode_prodi', $kodeProdi)->first();
        $namaProdi = $prodiData ? $prodiData->nama_prodi : $kodeProdi;

        Log::info("User {$user->username} switched to prodi: {$kodeProdi}");

        return redirect()->back()->with('success', "Beralih ke prodi {$namaProdi}");
    }
}