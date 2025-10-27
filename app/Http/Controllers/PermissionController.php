<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::with('roles');

        // Filter berdasarkan role
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter berdasarkan pencarian
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $permissions = $query->paginate(10);

        $roles = Role::pluck('name', 'name');

        return view('permissions.index', compact('permissions', 'roles'));
    }

    public function show(Permission $permission)
    {
        // Load relasi yang diperlukan
        $permission->load(['roles.permissions']);
        
        return view('permissions.show', compact('permission'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|unique:permissions,name'
            ]);

            $permission = Permission::create($request->all());
            // Log aktivitas
            ActivityLogService::log(
                'created',
                'permissions',
                'Created new permission: ' . $permission->name,
                $permission,
                null,
                $permission->fresh()->toArray()
            );
            return redirect()->route('permissions.index')->with('success', 'Permission Berhasil Di Buat.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Permission Gagal Di Buat: ' . $e->getMessage());
        }
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        try {
            $request->validate([
                'name' => 'required|unique:permissions,name,' . $permission->id
            ]);

            $oldData = $permission->toArray();
            $permission->update($request->all());
            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'permissions',
                'Updated permission: ' . $permission->name,
                $permission,
                $oldData,
                $permission->fresh()->toArray()
            );
            return redirect()->route('permissions.index')->with('success', 'Permission Berhasil Di Update');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Permission Gagal Di Update: ' . $e->getMessage());
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            $oldData = $permission->toArray();
            $permission->roles()->detach();
            $permission->delete();

            // Log aktivitas
            ActivityLogService::log(
                'deleted',
                'permissions',
                'Deleted permission: ' . $permission->name,
                $permission,
                $oldData,
                null
            );
            return redirect()->route('permissions.index')->with('success', 'Permission Berhasil Di Hapus');
        } catch (\Exception $e) {
            return redirect()->route('permissions.index')->with('error', 'Permission Gagal Di Hapus: ' . $e->getMessage());
        }
    }
}