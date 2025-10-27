<?php

namespace App\Http\Controllers;

use App\Models\JudulKriteriaDokumen;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class JudulKriteriaDokumenController extends Controller
{
    public function index(Request $request)
    {
        $query = JudulKriteriaDokumen::query();

        if ($request->filled('search')) {
            $query->where('nama_kriteria_dokumen', 'like', "%{$request->search}%");
        }

        $judulKriteriaDokumen = $query->latest()->paginate(10);
        return view('judul-kriteria-dokumen.index', compact('judulKriteriaDokumen'));
    }

    public function create()
    {
        return view('judul-kriteria-dokumen.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kriteria_dokumen' => 'required|string|max:255'
        ]);

        try {
            $judulKriteriaDokumen = JudulKriteriaDokumen::create($request->all());

            ActivityLogService::log(
                'created',
                'Judul_Kriteria_Dokumen',
                'Created new judul kriteria: ' . $judulKriteriaDokumen->nama_kriteria_dokumen,
                $judulKriteriaDokumen,
                null,
                $judulKriteriaDokumen->fresh()->toArray()
            );

            return redirect()->route('judul-kriteria-dokumen.index')
                ->with('success', 'Judul Kriteria Dokumen berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambahkan Judul Kriteria Dokumen: ' . $e->getMessage());
        }
    }

    public function show(JudulKriteriaDokumen $judulKriteriaDokumen)
    {
        return view('judul-kriteria-dokumen.show', compact('judulKriteriaDokumen'));
    }

    public function edit(JudulKriteriaDokumen $judulKriteriaDokumen)
    {
        return view('judul-kriteria-dokumen.edit', compact('judulKriteriaDokumen'));
    }

    public function update(Request $request, JudulKriteriaDokumen $judulKriteriaDokumen)
    {
        $request->validate([
            'nama_kriteria_dokumen' => 'required|string|max:255'
        ]);

        try {
            $oldData = $judulKriteriaDokumen->toArray();
            $judulKriteriaDokumen->update($request->all());

            ActivityLogService::log(
                'updated',
                'Judul_Kriteria_Dokumen',
                'Updated judul kriteria: ' . $judulKriteriaDokumen->nama_kriteria_dokumen,
                $judulKriteriaDokumen,
                $oldData,
                $judulKriteriaDokumen->fresh()->toArray()
            );

            return redirect()->route('judul-kriteria-dokumen.index')
                ->with('success', 'Judul Kriteria Dokumen berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal memperbarui Judul Kriteria Dokumen: ' . $e->getMessage());
        }
    }

    public function destroy(JudulKriteriaDokumen $judulKriteriaDokumen)
    {
        try {
            $oldData = $judulKriteriaDokumen->toArray();
            $judulKriteriaDokumen->delete();

            ActivityLogService::log(
                'deleted',
                'Judul_Kriteria_Dokumen',
                'Deleted judul kriteria: ' . $judulKriteriaDokumen->nama_kriteria_dokumen,
                $judulKriteriaDokumen,
                $oldData,
                null
            );

            return redirect()->route('judul-kriteria-dokumen.index')
                ->with('success', 'Judul Kriteria Dokumen berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus Judul Kriteria Dokumen: ' . $e->getMessage());
        }
    }
}