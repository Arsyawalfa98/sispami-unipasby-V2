<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Role;
use \App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create Super Admin Role
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->permissions()->attach(Permission::all());

        // Create Admin Role
        $admin = Role::create(['name' => 'Admin']);
        $admin->permissions()->attach(
            Permission::whereIn('name', [
                'view-users',
                'view-roles',
                'view-permissions'
            ])->get()
        );
    }
}
