<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->user()->hasPermission($permission)) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda Tidak Mempunyai Kewenangan Di Aksi Ini !!!'
                    // 'message' => 'Unauthorized action.'
                ], 403);
            }
            
            return back()->with('error', 'Anda Tidak Mempunyai Kewenangan Di Aksi Ini !!!');
            // return back()->with('error', 'Unauthorized action.');
        }

        return $next($request);
    }
}