<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\User;
use \App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Buat user Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'example@superadmin.com',
            'last_name' => 'superadmin',
            'password' => 'rahasia' // atau Hash::make('rahasia')
        ]);

        // Attach role Super Admin
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $superAdmin->roles()->attach($superAdminRole);
        }

        // Buat user Admin
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'last_name' => 'admin',
            'email' => 'example@admin.com',
            'password' => 'rahasia'
        ]);

        // Attach role Admin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole);
        }
    }
}
