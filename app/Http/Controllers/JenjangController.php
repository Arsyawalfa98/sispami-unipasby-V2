<?php

namespace App\Http\Controllers;

use App\Models\Jenjang;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class JenjangController extends Controller
{
    public function index(Request $request)
    {
        $query = Jenjang::query();

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $jenjang = $query->latest()->paginate(10);
        return view('jenjang.index', compact('jenjang'));
    }

    public function show(Jenjang $jenjang)
    {
        return view('jenjang.show', compact('jenjang'));
    }

    public function create()
    {
        return view('jenjang.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:jenjang,nama'
        ]);

        try {
            $jenjang = Jenjang::create($request->all());

            ActivityLogService::log(
                'created',
                'jenjang',
                'Created new jenjang: ' . $jenjang->nama,
                $jenjang,
                null,
                $jenjang->fresh()->toArray()
            );

            return redirect()->route('jenjang.index')
                ->with('success', 'Jenjang berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambahkan Jenjang: ' . $e->getMessage());
        }
    }

    public function edit(Jenjang $jenjang)
    {
        return view('jenjang.edit', compact('jenjang'));
    }

    public function update(Request $request, Jenjang $jenjang)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:jenjang,nama,' . $jenjang->id
        ]);

        try {
            $oldData = $jenjang->toArray();
            $jenjang->update($request->all());

            ActivityLogService::log(
                'updated',
                'jenjang',
                'Updated jenjang: ' . $jenjang->nama,
                $jenjang,
                $oldData,
                $jenjang->fresh()->toArray()
            );

            return redirect()->route('jenjang.index')
                ->with('success', 'Jenjang berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal memperbarui Jenjang: ' . $e->getMessage());
        }
    }

    public function destroy(Jenjang $jenjang)
    {
        try {
            $oldData = $jenjang->toArray();
            $jenjang->delete();

            ActivityLogService::log(
                'deleted',
                'jenjang',
                'Deleted jenjang: ' . $jenjang->nama,
                $jenjang,
                $oldData,
                null
            );

            return redirect()->route('jenjang.index')
                ->with('success', 'Jenjang berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus Jenjang: ' . $e->getMessage());
        }
    }
}