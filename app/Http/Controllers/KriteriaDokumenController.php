<?php

namespace App\Http\Controllers;

use App\Models\KriteriaDokumen;
use App\Models\LembagaAkreditasi;
use App\Models\User;
use App\Models\LembagaAkreditasiDetail;
use App\Models\Jenjang;
use App\Models\JadwalAmi;
use App\Models\JudulKriteriaDokumen;
use App\Models\PemenuhanDokumen;
use App\Models\PenilaianKriteria;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\KelolaKebutuhanKriteriaDokumen;

class KriteriaDokumenController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeJadwal = null;
    
        // Pengecekan untuk non-Super Admin
        if (!Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
            $userProdi = $user->prodi;
    
            // Pengecekan Jadwal AMI yang aktif
            $activeJadwal = JadwalAmi::where('prodi', 'like', "%{$userProdi}%")
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->first();
    
            // Jika tidak ada jadwal aktif, kembalikan view kosong
            if (!$activeJadwal) {
                return view('kriteria-dokumen.index', [
                    'kriteriaDokumen' => collect(),
                    'activeJadwal' => $activeJadwal
                ]);
            }
    
            // Filter berdasarkan prodi pengguna
            $allowedLembagaIds = LembagaAkreditasiDetail::where('prodi', 'like', "%{$userProdi}%")
                ->pluck('lembaga_akreditasi_id');
    
            $query = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
                ->select('lembaga_akreditasi_id', 'jenjang_id')
                ->whereIn('lembaga_akreditasi_id', $allowedLembagaIds)
                ->groupBy('lembaga_akreditasi_id', 'jenjang_id')
                ->distinct();
        } else {
            // Query untuk Super Admin (tanpa filter prodi)
            $query = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
                ->select('lembaga_akreditasi_id', 'jenjang_id')
                ->groupBy('lembaga_akreditasi_id', 'jenjang_id')
                ->distinct();
        }
    
        // Pengecekan pencarian (search)
        if ($request->filled('search')) {
            $query->whereHas('lembagaAkreditasi', function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%");
            })->orWhereHas('jenjang', function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%");
            });
        }
    
        // Ambil data kriteria dokumen
        $kriteriaDokumen = $query->get()->map(function ($item) {
            return KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $item->lembaga_akreditasi_id,
                'jenjang_id' => $item->jenjang_id
            ])->with(['lembagaAkreditasi', 'jenjang'])->first();
        });
    
        // Pagination
        $page = request()->get('page', 1);
        $perPage = 10;
        $items = $kriteriaDokumen->forPage($page, $perPage);
    
        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $kriteriaDokumen->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => $request->query()]
        );

        $allLembaga = LembagaAkreditasi::select('id', 'nama', 'tahun')->orderBy('nama')->orderBy('tahun', 'desc')->get();
        $allJenjang = Jenjang::select('id', 'nama')->orderBy('nama')->get();
    
        return view('kriteria-dokumen.index', [
            'kriteriaDokumen' => $paginatedData,
            'activeJadwal' => $activeJadwal ?? null,
            'allLembaga' => $allLembaga,
            'allJenjang' => $allJenjang
        ]);
    }

    public function create()
    {
        try {
            $user = Auth::user();
    
            if (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM')) {
                // Ubah untuk mengambil semua kolom yang dibutuhkan
                $lembagaAkreditasi = LembagaAkreditasi::select('id', 'nama', 'tahun')
                    ->get();
            } else {
                $userProdi = $user->prodi;
                $userFakultas = $user->fakultas;
    
                $allowedLembagaIds = LembagaAkreditasiDetail::where(function ($query) use ($userProdi, $userFakultas) {
                    $query->where('prodi', $userProdi)
                        ->orWhere('fakultas', $userFakultas);
                })->pluck('lembaga_akreditasi_id');
    
                $lembagaAkreditasi = LembagaAkreditasi::whereIn('id', $allowedLembagaIds)
                    ->select('id', 'nama', 'tahun')
                    ->get();
            }
    
            $jenjang = Jenjang::select('id', 'nama')->get();
    
            return view('kriteria-dokumen.create', compact('lembagaAkreditasi', 'jenjang'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'lembaga_akreditasi_id' => 'required',
            'jenjang_id' => 'required',
        ]);

        try {
            // Cek apakah kombinasi lembaga dan jenjang sudah ada
            $exists = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $request->lembaga_akreditasi_id,
                'jenjang_id' => $request->jenjang_id,
            ])->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->with('error', 'Data untuk Lembaga Akreditasi dan Jenjang ini sudah ada!');
            }

            // Ambil tahun dari lembaga akreditasi
            $lembagaAkreditasi = LembagaAkreditasi::findOrFail($request->lembaga_akreditasi_id);
            
            // Buat array data dengan periode_atau_tahun
            $data = $request->all();
            $data['periode_atau_tahun'] = $lembagaAkreditasi->tahun;

            $kriteriaDokumen = KriteriaDokumen::create($data);
            // Untuk create kriteria dokumen group
            $lembaga = LembagaAkreditasi::find($kriteriaDokumen->lembaga_akreditasi_id);
            $jenjang = Jenjang::find($kriteriaDokumen->jenjang_id);

            ActivityLogService::log(
                'created',
                'kriteria_dokumen',
                'Created new kriteria dokumen group - ' . $kriteriaDokumen->lembaga_akreditasi_id . '-' . $lembaga->nama . ' and ' . $kriteriaDokumen->jenjang_id . '-' . $jenjang->nama,
                $kriteriaDokumen,
                null,
                $kriteriaDokumen->fresh()->toArray()
            );

            return redirect()->route('kriteria-dokumen.index')
                ->with('success', 'Kriteria Dokumen berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan data: ' . $e->getMessage());
        }
    }

    public function showGroup($lembagaId, $jenjangId)
    {
        $kriteriaDokumen = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang', 'judulKriteriaDokumen'])
            ->where('lembaga_akreditasi_id', $lembagaId)
            ->where('jenjang_id', $jenjangId)
            ->get()
            ->groupBy('judulKriteriaDokumen.nama_kriteria_dokumen');

        $lembaga = LembagaAkreditasi::findOrFail($lembagaId);
        $jenjang = Jenjang::findOrFail($jenjangId);

        return view('kriteria-dokumen.show-group', compact('kriteriaDokumen', 'lembaga', 'jenjang'));
    }

    public function createDetail($lembagaId, $jenjangId)
    {
        $lembaga = LembagaAkreditasi::findOrFail($lembagaId);
        $jenjang = Jenjang::findOrFail($jenjangId);
        $judulKriteria = JudulKriteriaDokumen::select('id', 'nama_kriteria_dokumen')->get();
    
        return view('kriteria-dokumen.create-detail', compact(
            'lembaga',
            'jenjang',
            'judulKriteria',
        ));
    }

    public function storeDetail(Request $request, $lembagaId, $jenjangId)
    {
        $request->validate([
            'judul_kriteria_dokumen_id' => 'required',
            'kode' => 'required|string|max:255',
            'element' => 'required',
            'indikator' => 'required',
            'informasi' => 'required',
            'kebutuhan_dokumen' => 'required|integer',
            // 'bobot' => 'required|numeric|min:0|max:100' // Tambahkan validasi bobot
        ]);
        
        try {
            // Ambil tahun dari lembaga akreditasi
            $lembagaAkreditasi = LembagaAkreditasi::findOrFail($lembagaId);
    
            $kriteriaDokumen = KriteriaDokumen::create([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId,
                'judul_kriteria_dokumen_id' => $request->judul_kriteria_dokumen_id,
                'periode_atau_tahun' => $lembagaAkreditasi->tahun,
                'kode' => $request->kode,
                'element' => $request->element,
                'indikator' => $request->indikator,
                'informasi' => $request->informasi,
                'kebutuhan_dokumen' => $request->kebutuhan_dokumen,
                'bobot' => 0 // Tambahkan bobot
            ]);
    
            // Activity log dan respon redirect tetap sama
            return redirect()
                ->route('kriteria-dokumen.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId])
                ->with('success', 'Detail Kriteria berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan detail: ' . $e->getMessage());
        }
    }
    
    // Method untuk CRUD detail
    public function show(KriteriaDokumen $kriteriaDokumen)
    {
        $kriteriaDokumen->load([
            'lembagaAkreditasi', 
            'jenjang', 
            'judulKriteriaDokumen',
            'kebutuhanDokumen.tipeDokumen'
        ]);
        return view('kriteria-dokumen.show', compact('kriteriaDokumen'));
    }

    public function edit(KriteriaDokumen $kriteriaDokumen)
    {
        // Cek role user
        $user = Auth::user();
    
        if (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM')) {
            // Jika super admin, tampilkan semua lembaga
            $lembagaAkreditasi = LembagaAkreditasi::select('id', 'nama')->get();
        } else {
            // Jika bukan super admin, filter berdasarkan prodi dan fakultas
            $userProdi = $user->prodi;
            $userFakultas = $user->fakultas;
    
            $allowedLembagaIds = LembagaAkreditasiDetail::where(function ($query) use ($userProdi, $userFakultas) {
                $query->where('prodi', $userProdi)
                    ->orWhere('fakultas', $userFakultas);
            })->pluck('lembaga_akreditasi_id');
    
            $lembagaAkreditasi = LembagaAkreditasi::whereIn('id', $allowedLembagaIds)
                ->select('id', 'nama')
                ->get();
        }
    
        $jenjang = Jenjang::select('id', 'nama')->get();
        $judulKriteria = JudulKriteriaDokumen::select('id', 'nama_kriteria_dokumen')->get();
    
        return view('kriteria-dokumen.edit', compact(
            'kriteriaDokumen',
            'lembagaAkreditasi',
            'jenjang',
            'judulKriteria'
        ));
    }

    public function update(Request $request, KriteriaDokumen $kriteriaDokumen){
    
        $request->validate([
            'kode' => 'required|string|max:255',
            'element' => 'required',
            'indikator' => 'required',
            'informasi' => 'required',
            'kebutuhan_dokumen' => 'required|integer',
            'bobot' => 'required|numeric|min:0|max:100'
        ]);
        
        try {
             // Cek apakah ada data kelola kebutuhan untuk kriteria ini
            $hasKelolaKebutuhan = $kriteriaDokumen->kelolaKebutuhanKriteriaDokumen()->exists();
            
            if (!$hasKelolaKebutuhan) {
                return back()
                    ->withInput()
                    ->with('error', 'Gagal memperbarui : Anda harus membuat kelola kebutuhan terlebih dahulu untuk kriteria ini.');
            }
            $oldData = $kriteriaDokumen->toArray();
            $kriteriaDokumen->update($request->all());
    
            // Activity log dan respon redirect tetap sama
            return redirect()
                ->route('kriteria-dokumen.showGroup', [
                    'lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id,
                    'jenjangId' => $kriteriaDokumen->jenjang_id
                ])
                ->with('success', 'Detail Kriteria berhasil diperbarui');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui detail: ' . $e->getMessage());
        }
    }

    public function destroy(KriteriaDokumen $kriteriaDokumen)
    {
        try {
            $kriteriaDokumenId = $kriteriaDokumen->id;
            $periode = $kriteriaDokumen->periode_atau_tahun;
            
            // Periksa apakah data ini digunakan di tabel pemenuhan_dokumen menggunakan model
            $pemenuhanDokumenCount = PemenuhanDokumen::where('kriteria_dokumen_id', $kriteriaDokumenId)
                ->count();
                
            // Periksa apakah data ini digunakan di tabel penilaian_kriteria menggunakan model
            $penilaianKriteriaCount = PenilaianKriteria::where('kriteria_dokumen_id', $kriteriaDokumenId)
                ->where('periode_atau_tahun', $periode)
                ->count();
                
            // Jika data digunakan di salah satu atau kedua tabel, berikan pesan error
            if ($pemenuhanDokumenCount > 0 || $penilaianKriteriaCount > 0) {
                $message = 'Tidak dapat menghapus kriteria dokumen ini karena masih digunakan di: ';
                
                if ($pemenuhanDokumenCount > 0) {
                    $message .= 'Pemenuhan Dokumen (' . $pemenuhanDokumenCount . ' data)';
                }
                
                if ($penilaianKriteriaCount > 0) {
                    $message .= ($pemenuhanDokumenCount > 0 ? ' dan ' : '') . 'Penilaian Kriteria (' . $penilaianKriteriaCount . ' data)';
                }
                
                return back()->with('error', $message);
            }
            
            // Jika tidak ada ketergantungan, lanjutkan dengan penghapusan
            $oldData = $kriteriaDokumen->toArray();
            $lembagaId = $kriteriaDokumen->lembaga_akreditasi_id;
            $jenjangId = $kriteriaDokumen->jenjang_id;
    
            $kriteriaDokumen->delete();
    
            // Untuk delete
            $lembaga = LembagaAkreditasi::find($kriteriaDokumen->lembaga_akreditasi_id);
            $jenjang = Jenjang::find($kriteriaDokumen->jenjang_id);
            $judulKriteria = $kriteriaDokumen->judul_kriteria_dokumen_id ?
                JudulKriteriaDokumen::find($kriteriaDokumen->judul_kriteria_dokumen_id) : null;
    
            $description = 'Deleted kriteria ' .
                ($judulKriteria ? 'detail' : 'dokumen group') .
                ' for ' . $kriteriaDokumen->lembaga_akreditasi_id . '-' . $lembaga->nama .
                ' and ' . $kriteriaDokumen->jenjang_id . '-' . $jenjang->nama;
    
            if ($judulKriteria) {
                $description .= ' and ' . $kriteriaDokumen->judul_kriteria_dokumen_id . '-' . $judulKriteria->nama_kriteria_dokumen;
            }
    
            ActivityLogService::log(
                'deleted',
                'kriteria_dokumen',
                'Deleted kriteria detail - ' . $description,
                $kriteriaDokumen,
                $oldData,
                null
            );
    
            return redirect()
                ->route('kriteria-dokumen.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId])
                ->with('success', 'Detail Kriteria berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus detail: ' . $e->getMessage());
        }
    }

    public function destroyGroup($lembagaId, $jenjangId)
    {
        try {
            // Cek apakah ada detail
            $hasDetails = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->whereNotNull('judul_kriteria_dokumen_id')->exists();

            if ($hasDetails) {
                return back()->with('error', 'Tidak dapat menghapus grup ini karena masih memiliki detail kriteria. Hapus semua detail terlebih dahulu.');
            }

            // Ambil data sebelum dihapus untuk log
            $kriteriaDokumen = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->first();

            // Simpan data yang akan dihapus
            $oldData = $kriteriaDokumen ? $kriteriaDokumen->toArray() : null;

            // Hapus data
            $kriteriaDokumen->delete();
            // Untuk delete
            $lembaga = LembagaAkreditasi::find($kriteriaDokumen->lembaga_akreditasi_id);
            $jenjang = Jenjang::find($kriteriaDokumen->jenjang_id);
            $judulKriteria = $kriteriaDokumen->judul_kriteria_dokumen_id ?
                JudulKriteriaDokumen::find($kriteriaDokumen->judul_kriteria_dokumen_id) : null;

            $description = 'Deleted kriteria ' .
                ($judulKriteria ? 'detail' : 'dokumen group') .
                ' for ' . $kriteriaDokumen->lembaga_akreditasi_id . '-' . $lembaga->nama .
                ' and ' . $kriteriaDokumen->jenjang_id . '-' . $jenjang->nama;

            if ($judulKriteria) {
                $description .= ' and ' . $kriteriaDokumen->judul_kriteria_dokumen_id . '-' . $judulKriteria->nama_kriteria_dokumen;
            }

            ActivityLogService::log(
                'deleted',
                'kriteria_dokumen',
                'Deleted kriteria dokumen group for lembaga ' . $lembagaId . ' and jenjang ' . $jenjangId . '-' . $description,
                $kriteriaDokumen,  // Kirim object kriteriaDokumen
                $oldData,
                null
            );

            return redirect()->route('kriteria-dokumen.index')
                ->with('success', 'Kriteria Dokumen berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus Kriteria Dokumen: ' . $e->getMessage());
        }
    }

    public function copyGroup(Request $request, $sourceLembagaId, $sourceJenjangId)
    {
        $request->validate([
            'dest_lembaga_akreditasi_id' => 'required|exists:lembaga_akreditasi,id',
            'dest_jenjang_id' => 'required|exists:jenjang,id',
        ]);

        $destLembagaId = $request->input('dest_lembaga_akreditasi_id');
        $destJenjangId = $request->input('dest_jenjang_id');

        // 1. Cek jika tujuan sudah ada
        $exists = KriteriaDokumen::where('lembaga_akreditasi_id', $destLembagaId)
            ->where('jenjang_id', $destJenjangId)
            ->exists();

        if ($exists) {
            return redirect()->route('kriteria-dokumen.index')
                ->with('error', 'Kriteria dokumen untuk lembaga dan jenjang tujuan sudah ada. Tidak dapat menduplikasi.');
        }

        // 2. Cek jika source dan destination sama
        if ($sourceLembagaId == $destLembagaId && $sourceJenjangId == $destJenjangId) {
            return redirect()->route('kriteria-dokumen.index')
                ->with('error', 'Lembaga dan Jenjang tujuan tidak boleh sama dengan sumber.');
        }

        DB::beginTransaction();
        try {
            // Ambil tahun dari lembaga tujuan
            $destLembaga = LembagaAkreditasi::findOrFail($destLembagaId);
            $destTahun = $destLembaga->tahun;

            // 3. Ambil semua kriteria detail dari source
            $sourceKriterias = KriteriaDokumen::where('lembaga_akreditasi_id', $sourceLembagaId)
                ->where('jenjang_id', $sourceJenjangId)
                ->whereNotNull('judul_kriteria_dokumen_id') // Hanya detailnya
                ->with('kelolaKebutuhanKriteriaDokumen') // Eager load kebutuhan
                ->get();

            if ($sourceKriterias->isEmpty()){
                DB::rollBack();
                return redirect()->route('kriteria-dokumen.index')
                ->with('error', 'Kriteria sumber tidak memiliki detail untuk disalin.');
            }

            // Buat 'parent' group untuk kriteria baru
            $newGroup = KriteriaDokumen::create([
                'lembaga_akreditasi_id' => $destLembagaId,
                'jenjang_id' => $destJenjangId,
                'periode_atau_tahun' => $destTahun,
            ]);

            // 4. Loop dan duplikasi
            foreach ($sourceKriterias as $sourceKriteria) {
                // 4a. Duplikasi KriteriaDokumen (detail)
                $newKriteria = $sourceKriteria->replicate(['id']);
                $newKriteria->lembaga_akreditasi_id = $destLembagaId;
                $newKriteria->jenjang_id = $destJenjangId;
                $newKriteria->periode_atau_tahun = $destTahun;
                $newKriteria->save();

                // 4b. Duplikasi KelolaKebutuhanKriteriaDokumen
                if ($sourceKriteria->relationLoaded('kelolaKebutuhanKriteriaDokumen') && $sourceKriteria->kelolaKebutuhanKriteriaDokumen->isNotEmpty()) {
                    foreach ($sourceKriteria->kelolaKebutuhanKriteriaDokumen as $sourceKebutuhan) {
                        $newKebutuhan = $sourceKebutuhan->replicate(['id', 'kriteria_dokumen_id']);
                        $newKebutuhan->kriteria_dokumen_id = $newKriteria->id; // set foreign key baru
                        $newKebutuhan->save();
                    }
                }
            }

            $sourceLembaga = LembagaAkreditasi::find($sourceLembagaId);
            $sourceJenjang = Jenjang::find($sourceJenjangId);
            $destJenjang = Jenjang::find($destJenjangId);

            ActivityLogService::log(
                'created',
                'kriteria_dokumen',
                'Menyalin grup kriteria dokumen dari ' . $sourceLembaga->nama . ' (' . $sourceJenjang->nama . ') ke ' . $destLembaga->nama . ' (' . $destJenjang->nama . ')',
                $newGroup,
                null,
                $newGroup->fresh()->toArray()
            );

            DB::commit();

            return redirect()->route('kriteria-dokumen.index')
                ->with('success', 'Kriteria dokumen dan kebutuhannya berhasil disalin.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('kriteria-dokumen.index')
                ->with('error', 'Terjadi kesalahan saat menyalin data: ' . $e->getMessage());
        }
    }
}
