<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::with(['parent', 'children']);

        // Filter berdasarkan tipe menu
        if ($request->filled('type')) {
            if ($request->type === 'parent') {
                $query->whereNull('parent_id');
            } elseif ($request->type === 'child') {
                $query->whereNotNull('parent_id');
            }
        }

        // Filter berdasarkan pencarian
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $menus = $query->paginate(10);
        
        $menuTypes = [
            'parent' => 'Parent Menu',
            'child' => 'Child Menu'
        ];

        return view('menus.index', compact('menus', 'menuTypes'));
    }

    public function create()
    {
        $parentMenus = Menu::whereNull('parent_id')->get();
        return view('menus.create', compact('parentMenus'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'url' => 'nullable',
                'icon' => 'nullable',
                'parent_id' => 'nullable|exists:menus,id',
                'order' => 'required|numeric',
                'is_active' => 'boolean'
            ]);
            // Siapkan data
            $data = [
                'name' => $request->name,
                'url' => $request->url,
                'icon' => $request->icon,
                'parent_id' => $request->parent_id,
                'order' => $request->order,
                'is_active' => $request->has('is_active')
            ];

            $menu = Menu::create($data);
            // Log aktivitas
            ActivityLogService::log(
                'created',
                'menus',
                'Created new menu: ' . $menu->name,
                $menu,
                null,
                $menu->fresh()->toArray()
            );
            return redirect()->route('menus.index')->with('success', 'Menu Berhasil Di Buat');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Menu Gagal Di Buat: ' . $e->getMessage());
        }
    }

    public function edit(Menu $menu)
    {
        $parentMenus = Menu::whereNull('parent_id')
            ->where('id', '!=', $menu->id)
            ->get();
        return view('menus.edit', compact('menu', 'parentMenus'));
    }
    public function show(Menu $menu)
    {
        // Load relasi yang diperlukan
        $menu->load(['roles.permissions']);
        
        return view('menus.show', compact('menu'));
    }

    public function update(Request $request, Menu $menu)
    {
        try {
            $request->validate([
                'name' => 'required',
                'url' => 'nullable',
                'icon' => 'nullable',
                'parent_id' => 'nullable|exists:menus,id',
                'order' => 'required|numeric',
                'is_active' => 'boolean'
            ]);

            $oldData = $menu->toArray();

            // Siapkan data
            $data = [
                'name' => $request->name,
                'url' => $request->url,
                'icon' => $request->icon,
                'parent_id' => $request->parent_id,
                'order' => $request->order,
                'is_active' => $request->has('is_active')
            ];

            $menu->update($data);

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'menus',
                'Updated menu: ' . $menu->name,
                $menu,
                $oldData,
                $menu->fresh()->toArray()
            );
            return redirect()->route('menus.index')->with('success', 'Menu Berhasil Di Update');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Menu Gagal Di Update: ' . $e->getMessage());
        }
    }
    
    public function destroy(Menu $menu)
    {
        try {
            $oldData = $menu->toArray();
            // Jika menu punya child, hapus relasinya dulu
            if($menu->children()->exists()) {
                $menu->children()->update(['parent_id' => null]);
            }
            
            // Hapus relasi dengan roles
            $menu->roles()->detach();
            $menu->delete();
             // Log aktivitas
            ActivityLogService::log(
                'deleted',
                'menus',
                'Deleted menu: ' . $menu->name,
                $menu,
                $oldData,
                null
            );
            return redirect()->route('menus.index')->with('success', 'Menu Berhasil Di Hapus');
        } catch (\Exception $e) {
            return redirect()->route('menus.index')->with('error', 'Menu Gagal Di Hapus: ' . $e->getMessage());
        }
    }
}