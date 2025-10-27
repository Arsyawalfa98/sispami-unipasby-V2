<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class MenuComposer
{
    public function compose(View $view)
    {
        $menus = collect(Config::get('menu'));
        
        // Check if user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            
            // Filter menu berdasarkan role user
            $filteredMenus = $menus->filter(function ($menu) use ($user) {
                if (!isset($menu['roles'])) {
                    return true;
                }
                
                return collect($menu['roles'])->contains(function ($role) use ($user) {
                    return $user->hasRole($role);
                });
            });
        } else {
            // Jika user belum login, tampilkan menu yang tidak memerlukan role
            $filteredMenus = $menus->filter(function ($menu) {
                return !isset($menu['roles']);
            });
        }

        $view->with('menus', $filteredMenus);
    }
}