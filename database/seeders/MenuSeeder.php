<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Create main menus
        $dashboard = Menu::create([
            'name' => 'Dashboard',
            'url' => '/home',
            'icon' => 'fas fa-fw fa-tachometer-alt',
            'order' => 1,
            'parent_id' => null
        ]);

        $userManagement = Menu::create([
            'name' => 'User Management',
            'url' => '#',
            'icon' => 'fas fa-fw fa-users',
            'order' => 2,
            'parent_id' => null
        ]);

        // Create sub-menus for User Management
        Menu::create([
            'name' => 'Users',
            'url' => '/users',
            'icon' => 'fas fa-fw fa-user',
            'order' => 1,
            'parent_id' => $userManagement->id
        ]);

        Menu::create([
            'name' => 'Roles',
            'url' => '/roles',
            'icon' => 'fas fa-fw fa-user-lock',
            'order' => 2,
            'parent_id' => $userManagement->id
        ]);

        Menu::create([
            'name' => 'Permissions',
            'url' => '/permissions',
            'icon' => 'fas fa-fw fa-key',
            'order' => 3,
            'parent_id' => $userManagement->id
        ]);

        // Create Settings menu
        $settings = Menu::create([
            'name' => 'Settings',
            'url' => '#',
            'icon' => 'fas fa-fw fa-cog',
            'order' => 3,
            'parent_id' => null
        ]);

        // Create sub-menu for Settings
        Menu::create([
            'name' => 'Profile',
            'url' => '/profile',
            'icon' => 'fas fa-fw fa-user',
            'order' => 1,
            'parent_id' => $settings->id
        ]);

        Menu::create([
            'name' => 'Menu Management',
            'url' => '/menus',
            'icon' => 'fas fa-fw fa-list',
            'order' => 4,
            'parent_id' => $userManagement->id
        ]);

        // Assign all menus to Super Admin role
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $allMenus = Menu::all();
            $superAdmin->menus()->attach($allMenus->pluck('id'));
        }
    }
}