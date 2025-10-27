<?php

return [
    [
        'name' => 'Dashboard',
        'icon' => 'fas fa-fw fa-tachometer-alt',
        'route' => 'home',
        'roles' => ['Admin Prodi', 'Super Admin'] // siapa saja yang bisa akses
    ],
    [
        'header' => 'User Management',
        'roles' => ['Super Admin']
    ],
    [
        'name' => 'Users',
        'icon' => 'fas fa-fw fa-users',
        'route' => 'users.index',
        'roles' => ['Super Admin']
    ],
    [
        'name' => 'Roles',
        'icon' => 'fas fa-fw fa-user-lock',
        'route' => 'roles.index',
        'roles' => ['Super Admin']
    ],
    [
        'name' => 'Permissions',
        'icon' => 'fas fa-fw fa-key',
        'route' => 'permissions.index',
        'roles' => ['Super Admin']
    ],
    [
        'header' => 'Settings',
        'roles' => ['Admin Prodi', 'Super Admin']
    ],
    [
        'name' => 'Profile',
        'icon' => 'fas fa-fw fa-user',
        'route' => 'profile',
        'roles' => ['Admin Prodi', 'Super Admin']
    ],
    [
        'name' => 'About',
        'icon' => 'fas fa-fw fa-hands-helping',
        'route' => 'about',
        'roles' => ['Admin Prodi', 'Super Admin']
    ],
];