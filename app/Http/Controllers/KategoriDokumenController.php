<?php

namespace App\Http\Controllers;

use App\Models\KategoriDokumen;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class KategoriDokumenController extends Controller
{
    //index lamanya
    // public function index()
    // {
    //     $kategoriList = KategoriDokumen::with('roles')->get();
    //     return view('kategori-dokumen.index', compact('kategoriList'));
    // }
    //index lamanya

    public function index(Request $request)
    {
        $query = KategoriDokumen::with('roles');

        if ($request->filled('search')) {
            $query->where('nama_kategori', 'like', "%{$request->search}%");
        }

        $kategoriList = $query->paginate(10);
        return view('kategori-dokumen.index', compact('kategoriList'));
    }

    public function show(KategoriDokumen $kategori)
    {
        $kategori->load(['roles']);
        return view('kategori-dokumen.show', compact('kategori'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('kategori-dokumen.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate(['nama_kategori' => 'required|string|max:255', 'roles' => 'required|array']);

        $kategori = KategoriDokumen::create(['nama_kategori' => $request->nama_kategori]);
        $kategori->roles()->attach($request->roles);

        ActivityLogService::log(
            'created',
            'kategori_dokumen',
            'Created new kategori: ' . $kategori->nama_kategori,
            $kategori,
            null,
            $kategori->fresh()->toArray()
        );

        return redirect()->route('kategori-dokumen.index')->with('success', 'Kategori berhasil dibuat');
    }

    public function edit(KategoriDokumen $kategori)
    {
        $roles = Role::all();
        $selectedRoles = $kategori->roles->pluck('id')->toArray();
        return view('kategori-dokumen.edit', compact('kategori', 'roles', 'selectedRoles'));
    }

    public function update(Request $request, KategoriDokumen $kategori)
    {
        $oldData = $kategori->toArray();

        $request->validate(['nama_kategori' => 'required|string|max:255', 'roles' => 'required|array']);

        $kategori->update(['nama_kategori' => $request->nama_kategori]);
        $kategori->roles()->sync($request->roles);

        ActivityLogService::log(
            'updated',
            'kategori_dokumen',
            'Updated kategori: ' . $kategori->nama_kategori,
            $kategori,
            $oldData,
            $kategori->fresh()->toArray()
        );

        return redirect()->route('kategori-dokumen.index')->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(KategoriDokumen $kategori)
    {
        $oldData = $kategori->toArray();

        $kategori->delete();

        ActivityLogService::log(
            'deleted',
            'kategori_dokumen',
            'Deleted kategori: ' . $kategori->nama_kategori,
            $kategori,
            $oldData,
            null
        );

        return redirect()->route('kategori-dokumen.index')->with('success', 'Kategori berhasil dihapus');
    }
}
