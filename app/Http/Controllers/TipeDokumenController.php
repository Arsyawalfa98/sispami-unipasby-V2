<?php

namespace App\Http\Controllers;

use App\Models\TipeDokumen;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class TipeDokumenController extends Controller
{
    public function index(Request $request)
    {
        $query = TipeDokumen::query();

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $tipeDokumen = $query->latest()->paginate(10);
        return view('tipe-dokumen.index', compact('tipeDokumen'));
    }

    public function create()
    {
        return view('tipe-dokumen.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255'
        ]);

        try {
            $tipeDokumen = TipeDokumen::create($request->all());

            ActivityLogService::log(
                'created',
                'tipe_dokumen',
                'Created new tipe dokumen: ' . $tipeDokumen->nama,
                $tipeDokumen,
                null,
                $tipeDokumen->fresh()->toArray()
            );

            return redirect()->route('tipe-dokumen.index')
                ->with('success', 'Tipe Dokumen berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambahkan Tipe Dokumen: ' . $e->getMessage());
        }
    }

    public function show(TipeDokumen $tipeDokumen)
    {
        return view('tipe-dokumen.show', compact('tipeDokumen'));
    }

    public function edit(TipeDokumen $tipeDokumen)
    {
        return view('tipe-dokumen.edit', compact('tipeDokumen'));
    }

    public function update(Request $request, TipeDokumen $tipeDokumen)
    {
        $request->validate([
            'nama' => 'required|string|max:255'
        ]);

        try {
            $oldData = $tipeDokumen->toArray();
            $tipeDokumen->update($request->all());

            ActivityLogService::log(
                'updated',
                'tipe_dokumen',
                'Updated tipe dokumen: ' . $tipeDokumen->nama,
                $tipeDokumen,
                $oldData,
                $tipeDokumen->fresh()->toArray()
            );

            return redirect()->route('tipe-dokumen.index')
                ->with('success', 'Tipe Dokumen berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal memperbarui Tipe Dokumen: ' . $e->getMessage());
        }
    }

    public function destroy(TipeDokumen $tipeDokumen)
    {
        try {
            $oldData = $tipeDokumen->toArray();
            $tipeDokumen->delete();

            ActivityLogService::log(
                'deleted',
                'tipe_dokumen',
                'Deleted tipe dokumen: ' . $tipeDokumen->nama,
                $tipeDokumen,
                $oldData,
                null
            );

            return redirect()->route('tipe-dokumen.index')
                ->with('success', 'Tipe Dokumen berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus Tipe Dokumen: ' . $e->getMessage());
        }
    }
}