<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetActiveRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Jika user login
        if (Auth::check()) {
            $user = Auth::user();
            
            // Jika user memiliki role
            if ($user->roles->isNotEmpty()) {
                // Jika belum ada active_role di session, set default ke role pertama
                if (!session()->has('active_role')) {
                    session(['active_role' => $user->roles->first()->name]);
                }
                
                // Pastikan active_role masih valid (user masih memiliki role tersebut)
                $userRoles = $user->roles->pluck('name')->toArray();
                if (!in_array(session('active_role'), $userRoles)) {
                    session(['active_role' => $user->roles->first()->name]);
                }
            }
        }
        
        return $next($request);
    }
}