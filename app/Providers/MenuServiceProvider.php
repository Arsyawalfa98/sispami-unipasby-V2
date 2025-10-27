<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

class MenuServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('layouts.admin', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $availableMenus = collect([]);
                
                // Ambil menu berdasarkan role aktif saja
                $activeRole = session('active_role');
                
                // Jika active_role tidak ada di session, gunakan role pertama
                if (!$activeRole && $user->roles->isNotEmpty()) {
                    $activeRole = $user->roles->first()->name;
                }
                
                // Ambil role yang aktif
                $activeRoleObj = $user->roles->where('name', $activeRole)->first();
                
                if ($activeRoleObj) {
                    $menuCollection = $activeRoleObj->menus->where('is_active', true);
                    
                    $parentMenus = $menuCollection->whereNull('parent_id')
                                                  ->where('is_active', true);

                    foreach ($parentMenus as $parentMenu) {
                        $menuItem = [
                            'name' => $parentMenu->name,
                            'icon' => $parentMenu->icon,
                            'url' => $parentMenu->url,
                            'order' => $parentMenu->order ?? 999,
                            'children' => collect([])
                        ];

                        $children = $menuCollection->where('parent_id', $parentMenu->id)
                                                  ->where('is_active', true)
                                                  ->sortBy(function ($child) {
                                                      return $child->order ?? 999;
                                                  });

                        foreach ($children as $child) {
                            $menuItem['children']->push([
                                'name' => $child->name,
                                'url' => $child->url,
                                'icon' => $child->icon,
                                'order' => $child->order ?? 999
                            ]);
                        }

                        if (!empty($menuItem['url']) || $menuItem['children']->isNotEmpty()) {
                            $availableMenus->push($menuItem);
                        }
                    }

                    // Urutkan parent menu berdasarkan order
                    $availableMenus = $availableMenus->sortBy(function ($menu) {
                        return $menu['order'] ?? 999;
                    })->values();
                }

                $view->with('sidebarMenus', $availableMenus);
            } else {
                $view->with('sidebarMenus', collect([]));
            }
        });
    }
}