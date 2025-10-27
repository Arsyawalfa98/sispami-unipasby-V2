<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with(['permissions', 'menus']);

        // Filter berdasarkan permission
        if ($request->filled('permission')) {
            $query->whereHas('permissions', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->permission}%");
            });
        }

        // Filter berdasarkan pencarian
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $roles = $query->paginate(10);

        $permissions = Permission::pluck('name', 'name');

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function show(Role $role)
    {
        // Load relasi yang diperlukan
        $role->load(['permissions', 'users', 'menus']);  // Load relasi yang benar
        
        return view('roles.show', compact('role'));
    }

    public function create()
    {
        $permissions = Permission::all();
        $menus = Menu::with('children')->whereNull('parent_id')->get();
        return view('roles.create', compact('permissions', 'menus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array',
            'menus' => 'nullable|array'  // Tambahkan validasi untuk menus
        ]);

        try {
            $role = Role::create([
                'name' => $request->name
            ]);

            // Sync permissions
            if($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            // Sync menus
            if($request->has('menus')) {
                $role->menus()->sync($request->menus);
            }
            // Log aktivitas
            ActivityLogService::log(
                'created',
                'roles',
                'Created new role: ' . $role->name,
                $role,
                null,
                $role->fresh()->load(['permissions', 'menus'])->toArray()
            );

            return redirect()->route('roles.index')->with('success', 'Role Berhasil Di Buat');
                
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Role Gagal Di Buat: ' . $e->getMessage());
        }
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $menus = Menu::with('children')->whereNull('parent_id')->get();
        return view('roles.edit', compact('role', 'permissions', 'menus'));
    }

    public function update(Request $request, Role $role)
    {
        try {
            $request->validate([
                'name' => 'required|unique:roles,name,' . $role->id,
                'permissions' => 'required|array',
                'menus' => 'nullable|array'
            ]);
            // Simpan data lama sebelum update
            $oldData = $role->load(['permissions', 'menus'])->toArray();

            $role->update($request->only('name'));
            $role->permissions()->sync($request->permissions ?? []);
            $role->menus()->sync($request->menus ?? []);

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'roles',
                'Updated role: ' . $role->name,
                $role,
                $oldData,
                $role->fresh()->load(['permissions', 'menus'])->toArray()
            );
            
            return redirect()->route('roles.index')->with('success', 'Role Berhasil Di Update');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Role Gagal Di Update: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        try {
            // Simpan data sebelum dihapus
            $oldData = $role->load(['permissions', 'menus'])->toArray();

            $role->permissions()->detach();
            $role->users()->detach();
            $role->menus()->detach();
            $role->delete();
            
            // Log aktivitas
            ActivityLogService::log(
                'deleted',
                'roles',
                'Deleted role: ' . $role->name,
                $role,
                $oldData,
                null
            );
            return redirect()->route('roles.index')->with('success', 'Role Berhasil Di Hapus');
        } catch (\Exception $e) {
            return redirect()->route('roles.index') ->with('error', 'Role Gagal Di Hapus: ' . $e->getMessage());
        }
    }
}