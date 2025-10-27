<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureProdiContext
 *
 * Middleware ini memastikan bahwa:
 * 1. Session 'active_prodi' selalu ter-set untuk user yang login
 * 2. User punya akses ke prodi yang ada di session
 * 3. Auto-fallback ke default prodi jika session invalid
 */
class EnsureProdiContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip jika user belum login
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Cek apakah ada prodi di pivot table
        $userProdis = $user->prodis;

        // Jika user tidak punya prodi di pivot table, skip (backward compatibility)
        if ($userProdis->isEmpty()) {
            return $next($request);
        }

        $activeProdi = session('active_prodi');

        // Validasi: apakah session prodi valid?
        $isValidProdi = !empty($activeProdi) && $user->hasAccessToProdi($activeProdi);

        // Jika tidak valid, set default prodi
        if (!$isValidProdi) {
            $defaultProdi = $user->defaultProdi;

            if ($defaultProdi) {
                session(['active_prodi' => $defaultProdi->kode_prodi]);
                Log::info("Set default prodi for user {$user->username}: {$defaultProdi->kode_prodi}");
            } else {
                // Fallback: set prodi pertama
                $firstProdi = $userProdis->first();
                if ($firstProdi) {
                    session(['active_prodi' => $firstProdi->kode_prodi]);
                    Log::info("Set first prodi for user {$user->username}: {$firstProdi->kode_prodi}");

                    // Set sebagai default untuk next time
                    $firstProdi->update(['is_default' => true]);
                }
            }
        }

        return $next($request);
    }
}
