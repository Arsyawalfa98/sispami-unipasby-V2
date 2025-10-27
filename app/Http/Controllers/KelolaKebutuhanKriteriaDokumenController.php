<?php

namespace App\Http\Controllers;

use App\Models\KelolaKebutuhanKriteriaDokumen;
use App\Models\KriteriaDokumen;
use App\Models\TipeDokumen;
use App\Models\PemenuhanDokumen;
use App\Models\PenilaianKriteria;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class KelolaKebutuhanKriteriaDokumenController extends Controller
{
    public function index(Request $request)
    {
        $kriteriaDokumen = KriteriaDokumen::with('lembagaAkreditasi', 'jenjang', 'judulKriteriaDokumen')
            ->findOrFail($request->kriteriaDokumenId);
        
        $kriteriaDokumen = KriteriaDokumen::findOrFail($request->kriteriaDokumenId);
        session(['current_kriteria_dokumen_id' => $kriteriaDokumen->id]); // Simpan ID di session

        $kelolaKebutuhan = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $request->kriteriaDokumenId)
            ->orderBy('created_at', 'desc')
            ->get();

        $countDokumen = $kelolaKebutuhan->count();
        $maxDokumen = $kriteriaDokumen->kebutuhan_dokumen;

        return view('kelola-kebutuhan-kriteria-dokumen.index', compact(
            'kriteriaDokumen',
            'kelolaKebutuhan',
            'countDokumen',
            'maxDokumen'
        ));
    }

    public function create(Request $request)
    {
        try {
            $kriteriaDokumenId = session('current_kriteria_dokumen_id');
            $kriteriaDokumen = KriteriaDokumen::findOrFail($kriteriaDokumenId);
            
            $countDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kriteriaDokumenId)
                ->count();
            
            if ($countDokumen >= $kriteriaDokumen->kebutuhan_dokumen) {
                return redirect()
                    ->route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => $kriteriaDokumenId])
                    ->with('error', 'Jumlah kebutuhan dokumen sudah mencapai batas maksimal');
            }

            // Ubah baris ini untuk mendapatkan id dan nama
            $tipeDokumen = TipeDokumen::orderBy('nama')->pluck('nama', 'id');
        
            return view('kelola-kebutuhan-kriteria-dokumen.create', compact('kriteriaDokumen', 'tipeDokumen'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_dokumen' => 'required|string|max:255',
            'tipe_dokumen' => 'required|exists:tipe_dokumen,id',
            'keterangan' => 'nullable|string'
        ]);
        
        $kriteriaDokumen = KriteriaDokumen::findOrFail($request->kriteria_dokumen_id);

        $countDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $request->kriteria_dokumen_id)
            ->count();

        if ($countDokumen >= $kriteriaDokumen->kebutuhan_dokumen) {
            return redirect()
                ->back()
                ->with('error', 'Jumlah kebutuhan dokumen sudah mencapai batas maksimal');
        }

        $kelolaKebutuhan = KelolaKebutuhanKriteriaDokumen::create($request->all());

        ActivityLogService::log(
            'created',
            'kelola_kebutuhan_kriteria_dokumen',
            'Created new kelola kebutuhan kriteria dokumen ' . $kelolaKebutuhan->nama_dokumen,
            $kelolaKebutuhan,
            null,
            $kelolaKebutuhan->fresh()->toArray()
        );

        return redirect()
            ->route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => $request->kriteria_dokumen_id])
            ->with('success', 'Kebutuhan dokumen berhasil ditambahkan');
    }

    public function edit(KelolaKebutuhanKriteriaDokumen $kelolaKebutuhanKriteriaDokumen)
    {
        $kriteriaDokumen = $kelolaKebutuhanKriteriaDokumen->kriteriaDokumen;
        $tipeDokumen = TipeDokumen::orderBy('nama')->pluck('nama', 'id');
        return view('kelola-kebutuhan-kriteria-dokumen.edit', compact('kelolaKebutuhanKriteriaDokumen', 'kriteriaDokumen', 'tipeDokumen'));
    }

    public function update(Request $request, KelolaKebutuhanKriteriaDokumen $kelolaKebutuhanKriteriaDokumen)
    {
        $request->validate([
            'nama_dokumen' => 'required|string|max:255',
            'tipe_dokumen' => 'required|exists:tipe_dokumen,id',
            'keterangan' => 'nullable|string'
        ]);
        $oldData = $kelolaKebutuhanKriteriaDokumen->toArray();

        $kelolaKebutuhanKriteriaDokumen->update($request->all());

        ActivityLogService::log(
            'updated',
            'kelola_kebutuhan_kriteria_dokumen',
            'Updated kelola kebutuhan kriteria dokumen ' . $kelolaKebutuhanKriteriaDokumen->nama_dokumen,
            $kelolaKebutuhanKriteriaDokumen,
            $oldData,
            $kelolaKebutuhanKriteriaDokumen->fresh()->toArray()
        );

        return redirect()
            ->route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => $kelolaKebutuhanKriteriaDokumen->kriteria_dokumen_id])
            ->with('success', 'Data berhasil diperbarui');
    }

    public function destroy(KelolaKebutuhanKriteriaDokumen $kelolaKebutuhanKriteriaDokumen)
    {
        try {
            $kelolaKebutuhanId = $kelolaKebutuhanKriteriaDokumen->id;
            $kriteriaDokumenId = $kelolaKebutuhanKriteriaDokumen->kriteria_dokumen_id;
            $namaDokumen = $kelolaKebutuhanKriteriaDokumen->nama_dokumen;
            
            // Dapatkan kriteria dokumen terkait untuk mendapatkan periode
            $kriteriaDokumen = KriteriaDokumen::find($kriteriaDokumenId);
            $periode = $kriteriaDokumen ? $kriteriaDokumen->periode_atau_tahun : null;
            
            // Periksa apakah data ini digunakan di tabel pemenuhan_dokumen
            $pemenuhanDokumenCount = PemenuhanDokumen::where('kriteria_dokumen_id', $kriteriaDokumenId)
                ->where('nama_dokumen', $namaDokumen)
                ->count();
                
            // Periksa apakah data ini digunakan di tabel penilaian_kriteria
            $penilaianKriteriaCount = 0;
            if ($periode) {
                $penilaianKriteriaCount = PenilaianKriteria::where('kriteria_dokumen_id', $kriteriaDokumenId)
                    ->where('periode_atau_tahun', $periode)
                    ->count();
            }
                
            // Jika data digunakan di salah satu atau kedua tabel, berikan pesan error
            if ($pemenuhanDokumenCount > 0 || $penilaianKriteriaCount > 0) {
                $message = 'Tidak dapat menghapus kebutuhan dokumen ini karena masih digunakan di: ';
                
                if ($pemenuhanDokumenCount > 0) {
                    $message .= 'Pemenuhan Dokumen (' . $pemenuhanDokumenCount . ' data)';
                }
                
                if ($penilaianKriteriaCount > 0) {
                    $message .= ($pemenuhanDokumenCount > 0 ? ' dan ' : '') . 'Penilaian Kriteria (' . $penilaianKriteriaCount . ' data)';
                }
                
                return back()->with('error', $message);
            }
            
            // Jika tidak ada ketergantungan, lanjutkan dengan penghapusan
            $oldData = $kelolaKebutuhanKriteriaDokumen->toArray();
    
            $kelolaKebutuhanKriteriaDokumen->delete();
    
            ActivityLogService::log(
                'deleted',
                'kelola_kebutuhan_kriteria_dokumen',
                'Deleted kelola kebutuhan kriteria dokumen ' . $namaDokumen,
                $kelolaKebutuhanKriteriaDokumen,
                $oldData,
                null
            );
    
            return redirect()
                ->route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => $kriteriaDokumenId])
                ->with('success', 'Data berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function show(KelolaKebutuhanKriteriaDokumen $kelolaKebutuhanKriteriaDokumen)
    {
        return view('kelola-kebutuhan-kriteria-dokumen.show', compact('kelolaKebutuhanKriteriaDokumen'));
    }

    /**
     * Get available kriteria dokumen untuk di-copy (AJAX)
     */
    public function getAvailableKriteria(Request $request)
    {
        $currentKriteriaId = $request->kriteriaDokumenId;
        $currentKriteria = KriteriaDokumen::findOrFail($currentKriteriaId);

        // Ambil kriteria dokumen lain dengan jenjang yang sama (exclude current)
        $availableKriteria = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
            ->where('id', '!=', $currentKriteriaId)
            ->where('jenjang_id', $currentKriteria->jenjang_id)
            ->orderBy('periode_atau_tahun', 'desc')
            ->orderBy('lembaga_akreditasi_id')
            ->get()
            ->map(function ($kriteria) {
                $jumlahDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kriteria->id)->count();

                return [
                    'id' => $kriteria->id,
                    'label' => sprintf(
                        '%s - %s - %s (%d dokumen)',
                        $kriteria->lembagaAkreditasi->nama,
                        $kriteria->periode_atau_tahun,
                        $kriteria->jenjang->nama,
                        $jumlahDokumen
                    ),
                    'lembaga' => $kriteria->lembagaAkreditasi->nama,
                    'periode' => $kriteria->periode_atau_tahun,
                    'jenjang' => $kriteria->jenjang->nama,
                    'jumlah_dokumen' => $jumlahDokumen
                ];
            })
            ->filter(function ($item) {
                // Hanya tampilkan kriteria yang punya dokumen
                return $item['jumlah_dokumen'] > 0;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $availableKriteria
        ]);
    }

    /**
     * Preview dokumen dari kriteria yang akan di-copy (AJAX)
     */
    public function previewKriteria(Request $request)
    {
        $sourceKriteriaId = $request->sourceKriteriaId;
        $targetKriteriaId = $request->targetKriteriaId;

        $sourceDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $sourceKriteriaId)
            ->with('tipeDokumen')
            ->orderBy('nama_dokumen')
            ->get()
            ->map(function ($item) {
                return [
                    'nama_dokumen' => $item->nama_dokumen,
                    'tipe_dokumen' => $item->tipeDokumen ? $item->tipeDokumen->nama : '-',
                    'keterangan' => $item->keterangan ?? '-'
                ];
            });

        // Check existing dokumen di target (untuk deteksi duplikat)
        $existingDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $targetKriteriaId)
            ->pluck('nama_dokumen')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $sourceDokumen,
            'existing' => $existingDokumen,
            'will_skip' => count(array_intersect($sourceDokumen->pluck('nama_dokumen')->toArray(), $existingDokumen))
        ]);
    }

    /**
     * Copy kebutuhan dokumen dari kriteria lain
     */
    public function copyFromKriteria(Request $request)
    {
        try {
            $request->validate([
                'source_kriteria_id' => 'required|exists:kriteria_dokumen,id',
                'target_kriteria_id' => 'required|exists:kriteria_dokumen,id'
            ]);

            $sourceKriteriaId = $request->source_kriteria_id;
            $targetKriteriaId = $request->target_kriteria_id;

            // Validasi: source dan target tidak boleh sama
            if ($sourceKriteriaId == $targetKriteriaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menyalin dari kriteria yang sama'
                ], 400);
            }

            // Ambil target kriteria untuk validasi batas maksimal
            $targetKriteria = KriteriaDokumen::findOrFail($targetKriteriaId);
            $currentCount = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $targetKriteriaId)->count();
            $maxDokumen = $targetKriteria->kebutuhan_dokumen;

            // Ambil source dokumen
            $sourceDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $sourceKriteriaId)
                ->get();

            if ($sourceDokumen->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dokumen untuk disalin dari kriteria sumber'
                ], 400);
            }

            // Ambil nama dokumen yang sudah ada di target (untuk skip duplikat)
            $existingDokumenNames = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $targetKriteriaId)
                ->pluck('nama_dokumen')
                ->toArray();

            $copiedCount = 0;
            $skippedCount = 0;
            $limitReached = false;

            foreach ($sourceDokumen as $dokumen) {
                // Skip jika nama dokumen sudah ada
                if (in_array($dokumen->nama_dokumen, $existingDokumenNames)) {
                    $skippedCount++;
                    continue;
                }

                // Check batas maksimal
                if ($currentCount + $copiedCount >= $maxDokumen) {
                    $limitReached = true;
                    break;
                }

                // Copy dokumen
                KelolaKebutuhanKriteriaDokumen::create([
                    'kriteria_dokumen_id' => $targetKriteriaId,
                    'nama_dokumen' => $dokumen->nama_dokumen,
                    'tipe_dokumen' => $dokumen->tipe_dokumen,
                    'keterangan' => $dokumen->keterangan
                ]);

                $copiedCount++;
            }

            // Log activity
            $sourceKriteria = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])->find($sourceKriteriaId);
            ActivityLogService::log(
                'copied',
                'kelola_kebutuhan_kriteria_dokumen',
                sprintf(
                    'Copied %d dokumen from %s %s %s to kriteria %d',
                    $copiedCount,
                    $sourceKriteria->lembagaAkreditasi->nama,
                    $sourceKriteria->periode_atau_tahun,
                    $sourceKriteria->jenjang->nama,
                    $targetKriteriaId
                ),
                null,
                null,
                [
                    'source_kriteria_id' => $sourceKriteriaId,
                    'target_kriteria_id' => $targetKriteriaId,
                    'copied_count' => $copiedCount,
                    'skipped_count' => $skippedCount
                ]
            );

            $message = "Berhasil menyalin {$copiedCount} dokumen";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} dokumen dilewati karena sudah ada)";
            }
            if ($limitReached) {
                $message .= ". Batas maksimal dokumen tercapai";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'copied' => $copiedCount,
                    'skipped' => $skippedCount,
                    'limit_reached' => $limitReached
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
