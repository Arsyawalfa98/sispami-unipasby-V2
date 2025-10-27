<?php

namespace App\Http\Controllers;

use App\Models\LembagaAkreditasi;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;
use App\Models\Siakad;
use Illuminate\Support\Facades\DB;

class LembagaAkreditasiController extends Controller
{
    public function index(Request $request)
    {
        $query = LembagaAkreditasi::with('details');

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%")
                ->orWhereHas('details', function ($q) use ($request) {
                    $q->where('prodi', 'like', "%{$request->search}%")
                        ->orWhere('fakultas', 'like', "%{$request->search}%");
                });
        }

        $lembagaAkreditasi = $query->latest()->paginate(10);
        return view('lembaga-akreditasi.index', compact('lembagaAkreditasi'));
    }

    public function create()
    {
        return view('lembaga-akreditasi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tahun' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'prodi.*' => 'required|string',
            'fakultas.*' => 'required|string',
        ], [
            // Custom messages
            'nama.required' => 'Nama lembaga harus diisi',
            'tahun.required' => 'Tahun harus diisi',
            'prodi.*.required' => 'Harap lengkapi semua kolom Program Studi & Fakultas sebelum menyimpan. Jika Anda menekan tombol "Tambah Program Studi", pastikan form yang ditambahkan diisi dengan benar. Form kosong yang tidak terisi akan menyebabkan proses simpan gagal. Jika tidak digunakan, sebaiknya hapus form yang kosong tersebut.',
        ]);

        try {
            DB::beginTransaction();

            $lembaga = LembagaAkreditasi::create([
                'nama' => $request->nama,
                'tahun' => $request->tahun
            ]);

            // Simpan detail prodi dan fakultas
            foreach ($request->prodi as $key => $prodi) {
                $lembaga->details()->create([
                    'prodi' => $prodi,
                    'fakultas' => $request->fakultas[$key]
                ]);
            }

            ActivityLogService::log(
                'created',
                'lembaga_akreditasi',
                'Created new lembaga: ' . $lembaga->nama,
                $lembaga,
                null,
                $lembaga->fresh()->toArray()
            );

            DB::commit();
            return redirect()->route('lembaga-akreditasi.index')
                ->with('success', 'Lembaga Akreditasi berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            // return back()->withInput()->with('error', 'Gagal menambahkan Lembaga Akreditasi: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menambahkan Lembaga Akreditasi Karna Nama Lembaga Sudah Ada');
        }
    }


    public function show(LembagaAkreditasi $lembagaAkreditasi)
    {
        return view('lembaga-akreditasi.show', compact('lembagaAkreditasi'));
    }

    public function edit(LembagaAkreditasi $lembagaAkreditasi)
    {
        return view('lembaga-akreditasi.edit', compact('lembagaAkreditasi'));
    }

    public function update(Request $request, LembagaAkreditasi $lembagaAkreditasi)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tahun' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'prodi.*' => 'required|string',
            'fakultas.*' => 'required|string',
        ], [
            // Custom messages
            'nama.required' => 'Nama lembaga harus diisi',
            'tahun.required' => 'Tahun harus diisi',
            'prodi.*.required' => 'Harap lengkapi semua kolom Program Studi & Fakultas sebelum menyimpan. Jika Anda menekan tombol "Tambah Program Studi", pastikan form yang ditambahkan diisi dengan benar. Form kosong yang tidak terisi akan menyebabkan proses simpan gagal. Jika tidak digunakan, sebaiknya hapus form yang kosong tersebut.',
        ]);

        try {
            DB::beginTransaction();

            $oldData = $lembagaAkreditasi->toArray();

            $lembagaAkreditasi->update([
                'nama' => $request->nama,
                'tahun' => $request->tahun
            ]);

            // Hapus detail lama
            $lembagaAkreditasi->details()->delete();

            // Tambah detail baru
            foreach ($request->prodi as $key => $prodi) {
                $lembagaAkreditasi->details()->create([
                    'prodi' => $prodi,
                    'fakultas' => $request->fakultas[$key]
                ]);
            }

            ActivityLogService::log(
                'updated',
                'lembaga_akreditasi',
                'Updated lembaga: ' . $lembagaAkreditasi->nama,
                $lembagaAkreditasi,
                $oldData,
                $lembagaAkreditasi->fresh()->toArray()
            );

            DB::commit();
            return redirect()->route('lembaga-akreditasi.index')
                ->with('success', 'Lembaga Akreditasi berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Gagal memperbarui Lembaga Akreditasi: ' . $e->getMessage());
        }
    }

    public function destroy(LembagaAkreditasi $lembagaAkreditasi)
    {
        try {
            $oldData = $lembagaAkreditasi->toArray();
            $lembagaAkreditasi->delete();

            ActivityLogService::log(
                'deleted',
                'lembaga_akreditasi',
                'Deleted lembaga: ' . $lembagaAkreditasi->nama,
                $lembagaAkreditasi,
                $oldData,
                null
            );

            return redirect()->route('lembaga-akreditasi.index')
                ->with('success', 'Lembaga Akreditasi berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus Lembaga Akreditasi: ' . $e->getMessage());
        }
    }
}
