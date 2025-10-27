<?php

namespace App\Http\Controllers;

use App\Models\PenilaianKriteria;
use App\Models\KriteriaDokumen;
use App\Models\LembagaAkreditasi;
use App\Models\Jenjang;
use App\Models\Siakad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class LaporanStatusDokumenController extends Controller
{

    public function index(Request $request)
    {
        $filters = $request->only(['lembaga_akreditasi', 'tahun', 'jenjang', 'prodi', 'status']);
        $perPage = $request->get('per_page', 10);
        
        // Query dasar
        $query = PenilaianKriteria::query()
            ->select(
                'prodi',
                'fakultas',
                'status',
                'kriteria_dokumen_id',
                'periode_atau_tahun',
                DB::raw('MAX(updated_at) as last_updated')
            )
            ->with([
                'kriteriaDokumen.lembagaAkreditasi',
                'kriteriaDokumen.jenjang'
            ]);
        
        // Terapkan filter jika ada
        if (!empty($filters)) {
            // Filter Lembaga Akreditasi
            if (!empty($filters['lembaga_akreditasi'])) {
                $query->whereHas('kriteriaDokumen', function($q) use ($filters) {
                    $q->where('lembaga_akreditasi_id', $filters['lembaga_akreditasi']);
                });
            }
            
            // Filter Tahun
            if (!empty($filters['tahun'])) {
                $query->where('periode_atau_tahun', $filters['tahun']);
            }
            
            // Filter Jenjang
            if (!empty($filters['jenjang'])) {
                $query->whereHas('kriteriaDokumen', function($q) use ($filters) {
                    $q->where('jenjang_id', $filters['jenjang']);
                });
            }
            
            // Filter Prodi
            if (!empty($filters['prodi'])) {
                $query->where('prodi', 'like', '%' . $filters['prodi'] . '%');
            }
            
            // Filter Status
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }
        
        // Sesuaikan dengan role user
        $user = Auth::user();
        
        // Jika user adalah Admin, hanya tampilkan prodi mereka
        if ($user->hasActiveRole('Admin Prodi')) {
            $userProdi = $user->prodi;
            $query->where('prodi', $userProdi);
        }
        
        // Jika user adalah Admin Fakultas, hanya tampilkan fakultas mereka
        if ($user->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $query->where('fakultas', $userFakultas);
        }
        
        // Jika user adalah Auditor, hanya tampilkan prodi yang mereka tangani
        if ($user->hasActiveRole('Auditor')) {
            // Filter berdasarkan jadwal ami yang ditugaskan ke auditor
            $prodiAuditor = DB::table('jadwal_ami')
                ->where('tim_auditor', 'like', '%' . $user->name . '%')
                ->pluck('prodi')
                ->toArray();
                
            $query->whereIn('prodi', $prodiAuditor);
        }
        
        // Group by untuk menghilangkan duplikasi
        $query->groupBy([
            'prodi', 
            'fakultas', 
            'status',
            DB::raw('kriteria_dokumen_id'),
            DB::raw('periode_atau_tahun')
        ]);
        
        // Dapatkan data dan kelompokkan lagi untuk menghilangkan duplikat kriteria
        $penilaianRaw = $query->get();
        
        // Kelompokkan data untuk menghilangkan duplikasi pada level kriteria
        $penilaianGrouped = $penilaianRaw->groupBy(function($item) {
            // Kelompokkan berdasarkan kombinasi lembaga, jenjang, prodi, status
            $lembagaId = $item->kriteriaDokumen->lembaga_akreditasi_id ?? 'unknown';
            $jenjangId = $item->kriteriaDokumen->jenjang_id ?? 'unknown';
            return $lembagaId . '-' . $jenjangId . '-' . $item->prodi . '-' . $item->status;
        })->map(function($group) {
            // Ambil data pertama dari setiap kelompok
            $first = $group->first();
            // Simpan tanggal update terbaru
            $latestUpdate = $group->max('last_updated');
            $first->last_updated = $latestUpdate;
            return $first;
        })->values();
        
        // Terapkan paginasi manual
        $page = request('page', 1);
        $penilaianCollection = collect($penilaianGrouped);
        $penilaian = new \Illuminate\Pagination\LengthAwarePaginator(
            $penilaianCollection->forPage($page, $perPage),
            $penilaianCollection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        // Ambil daftar lembaga, jenjang, dan status untuk filter dropdown
        $lembagaList = LembagaAkreditasi::orderBy('nama')->get();
        
        // Ambil daftar jenjang
        $jenjangList = Jenjang::orderBy('nama')->get();
        
        // Ambil daftar tahun unik dari kriteria dokumen
        $tahunList = KriteriaDokumen::select(DB::raw('DISTINCT periode_atau_tahun as tahun'))
                                  ->orderBy('periode_atau_tahun', 'desc')
                                  ->get();
        
        // Daftar status dari konstanta model
        $statusList = [
            PenilaianKriteria::STATUS_DRAFT => 'Draft',
            PenilaianKriteria::STATUS_PENILAIAN => 'Penilaian',
            PenilaianKriteria::STATUS_DIAJUKAN => 'Diajukan',
            PenilaianKriteria::STATUS_DISETUJUI => 'Disetujui',
            PenilaianKriteria::STATUS_DITOLAK => 'Ditolak',
            PenilaianKriteria::STATUS_REVISI => 'Revisi'
        ];
        
        return view('laporan-status-dokumen.index', compact(
            'penilaian',
            'lembagaList',
            'jenjangList',
            'tahunList',
            'statusList',
            'filters'
        ));
    }

    public function searchProdi(Request $request)
    {
        try {
            $search = $request->search;
            
            // Log pencarian untuk debugging
            \Log::info("Searching for prodi with keyword: " . $search);
            
            // Gunakan Siakad::searchProdi seperti di UserController
            $prodis = Siakad::searchProdi($search);
            
            // Log hasil pencarian
            \Log::info("Found " . count($prodis) . " results from Siakad");
            
            // Format hasil pencarian seperti di UserController
            $formattedProdi = collect($prodis)->map(function ($prodi) {
                return [
                    'id' => $prodi->kodeunit . ' - ' . $prodi->namaunit,
                    'text' => $prodi->kodeunit . ' - ' . $prodi->namaunit
                ];
            });
            
            // Log hasil yang diformat
            \Log::info("Formatted results: " . $formattedProdi->count());
            
            return response()->json($formattedProdi);
        } catch (\Exception $e) {
            // Log error
            \Log::error("Error in searchProdi: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Return empty array dengan pesan error
            return response()->json([
                ['id' => '', 'text' => 'Error: ' . $e->getMessage()]
            ]);
        }
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['lembaga_akreditasi', 'tahun', 'jenjang', 'prodi', 'status']);

        // Query dasar
        $query = PenilaianKriteria::query()
            ->select(
                'prodi',
                'fakultas',
                'status',
                'kriteria_dokumen_id',
                'periode_atau_tahun',
                DB::raw('MAX(updated_at) as last_updated')
            )
            ->with([
                'kriteriaDokumen.lembagaAkreditasi',
                'kriteriaDokumen.jenjang'
            ]);

        // Terapkan filter jika ada
        if (!empty($filters)) {
            // Filter Lembaga Akreditasi
            if (!empty($filters['lembaga_akreditasi'])) {
                $query->whereHas('kriteriaDokumen', function ($q) use ($filters) {
                    $q->where('lembaga_akreditasi_id', $filters['lembaga_akreditasi']);
                });
            }

            // Filter Tahun
            if (!empty($filters['tahun'])) {
                $query->where('periode_atau_tahun', $filters['tahun']);
            }

            // Filter Jenjang
            if (!empty($filters['jenjang'])) {
                $query->whereHas('kriteriaDokumen', function ($q) use ($filters) {
                    $q->where('jenjang_id', $filters['jenjang']);
                });
            }

            // Filter Prodi
            if (!empty($filters['prodi'])) {
                $query->where('prodi', 'like', '%' . $filters['prodi'] . '%');
            }

            // Filter Status
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }

        // Sesuaikan dengan role user
        $user = Auth::user();

        // Jika user adalah Admin, hanya tampilkan prodi mereka
        if ($user->hasActiveRole('Admin Prodi')) {
            $userProdi = $user->prodi;
            $query->where('prodi', $userProdi);
        }

        // Jika user adalah Admin Fakultas, hanya tampilkan fakultas mereka
        if ($user->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $query->where('fakultas', $userFakultas);
        }

        // Jika user adalah Auditor, hanya tampilkan prodi yang mereka tangani
        if ($user->hasActiveRole('Auditor')) {
            // Filter berdasarkan jadwal ami yang ditugaskan ke auditor
            $prodiAuditor = DB::table('jadwal_ami')
                ->where('tim_auditor', 'like', '%' . $user->name . '%')
                ->pluck('prodi')
                ->toArray();

            $query->whereIn('prodi', $prodiAuditor);
        }

        // Group by untuk menghilangkan duplikasi
        $query->groupBy([
            'prodi',
            'fakultas',
            'status',
            DB::raw('kriteria_dokumen_id'),
            DB::raw('periode_atau_tahun')
        ]);

        // Dapatkan data dan kelompokkan lagi untuk menghilangkan duplikat kriteria
        $penilaianRaw = $query->get();

        // Kelompokkan data untuk menghilangkan duplikasi pada level kriteria
        $penilaian = $penilaianRaw->groupBy(function ($item) {
            // Kelompokkan berdasarkan kombinasi lembaga, jenjang, prodi, status
            $lembagaId = $item->kriteriaDokumen->lembaga_akreditasi_id ?? 'unknown';
            $jenjangId = $item->kriteriaDokumen->jenjang_id ?? 'unknown';
            return $lembagaId . '-' . $jenjangId . '-' . $item->prodi . '-' . $item->status;
        })->map(function ($group) {
            // Ambil data pertama dari setiap kelompok
            $first = $group->first();
            // Simpan tanggal update terbaru
            $latestUpdate = $group->max('last_updated');
            $first->last_updated = $latestUpdate;
            return $first;
        })->values();

        // Daftar status untuk tampilan
        $statusLabels = [
            PenilaianKriteria::STATUS_DRAFT => 'Draft',
            PenilaianKriteria::STATUS_PENILAIAN => 'Penilaian',
            PenilaianKriteria::STATUS_DIAJUKAN => 'Diajukan',
            PenilaianKriteria::STATUS_DISETUJUI => 'Disetujui',
            PenilaianKriteria::STATUS_DITOLAK => 'Ditolak',
            PenilaianKriteria::STATUS_REVISI => 'Revisi'
        ];

        // Generate PDF
        $pdf = PDF::loadView('laporan-status-dokumen.pdf', [
            'penilaian' => $penilaian,
            'statusLabels' => $statusLabels,
            'filters' => $filters
        ]);

        // Set paper size to A4 landscape
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'margin-top'    => 10,
            'margin-right'  => 10,
            'margin-bottom' => 10,
            'margin-left'   => 10,
        ]);

        // Generate filename
        $filename = 'Laporan_Status_Dokumen_' . date('Y-m-d') . '.pdf';

         // PERUBAHAN: Stream PDF di browser untuk preview
        return $pdf->stream($filename);
        
        // Untuk download langsung, gunakan:
        // return $pdf->download($filename);
    }
}
