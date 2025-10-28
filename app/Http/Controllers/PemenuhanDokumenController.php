<?php

namespace App\Http\Controllers;

use App\Models\PemenuhanDokumen;
use App\Models\PenilaianKriteria; // Tambahkan model baru
use App\Models\KriteriaDokumen;
use App\Models\JadwalAmi;
use App\Models\LembagaAkreditasiDetail;
use App\Models\KelolaKebutuhanKriteriaDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\LembagaAkreditasi;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\PemenuhanDokumen\PemenuhanDokumenService;
use App\Services\ActivityLogService;

class PemenuhanDokumenController extends Controller
{
    protected $pemenuhanDokumenService;

    public function __construct(PemenuhanDokumenService $pemenuhanDokumenService)
    {
        $this->pemenuhanDokumenService = $pemenuhanDokumenService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->only(['search']);
        $perPage = $request->get('per_page', 5); // Default 5 item per halaman
        
        // Ambil Query Builder dari service
        $query = $this->pemenuhanDokumenService->getFilteredData($filters);
        
        // Dapatkan semua tahun yang unik dari database
        $yearOptions = KriteriaDokumen::distinct()->pluck('periode_atau_tahun')->filter()->sort()->values()->all();
        
        // Dapatkan semua jenjang dari model Jenjang
        $jenjangOptions = \App\Models\Jenjang::pluck('nama', 'id')->toArray();
        
        $selectedStatus = $request->get('status');
        $selectedYear = $request->get('year');
        $selectedJenjang = $request->get('jenjang');

        // Apply filters to the Query Builder
        if ($selectedYear && $selectedYear !== 'all') {
            $query->where('periode_atau_tahun', $selectedYear);
        }
        
        if ($selectedJenjang && $selectedJenjang !== 'all') {
            $query->where('jenjang_id', $selectedJenjang);
        }

        // Apply pagination
        $kriteriaDokumenPaginator = $query->paginate($perPage)->withQueryString();
        $kriteriaDokumenCollection = $kriteriaDokumenPaginator->getCollection();

        // --- POST-PAGINATION PROCESSING FOR STATUS FILTER AND filtered_details ---
        $jadwalAmiList = $this->getJadwalAmiListForFiltering($user);
        $allStatuses = collect();

        foreach ($kriteriaDokumenCollection as $item) {
            // Apply getFilteredDetails to each item in the paginated collection
            $item->filtered_details = collect($this->pemenuhanDokumenService->getFilteredDetails($item, $jadwalAmiList));
            
            // Filter detail di dalam setiap item, bukan filter item keseluruhan
            if ($item->filtered_details) {
                $item->filtered_details = $item->filtered_details->filter(function($detail) {
                    $hasJadwal = !empty($detail['jadwal']);
                    $statusOk = $detail['status'] !== 'Belum ada jadwal';
                    return $hasJadwal && $statusOk;
                });
            }

            // Collect all unique statuses for dropdown
            if ($item->filtered_details) {
                $allStatuses = $allStatuses->merge($item->filtered_details->pluck('status'));
            }
        }

        // After filtering details, remove items that do not have valid details at all
        $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function($item) {
            return $item->filtered_details && $item->filtered_details->count() > 0;
        });

        // Filter tambahan untuk auditor (jika belum ditangani di repository)
        if ($user->hasActiveRole('Auditor')) {
            $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function($item) {
                return $item->filtered_details && $item->filtered_details->contains(function($detail) {
                    return !empty($detail['jadwal']);
                });
            });
        } 
        // Filter untuk Fakultas - hanya tampilkan item yang memiliki detail dengan fakultas yang sesuai
        elseif ($user->hasActiveRole('Fakultas')) {
            $userFakultas = $user->fakultas;
            $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function($item) use ($userFakultas) {
                return $item->filtered_details && $item->filtered_details->contains(function($detail) use ($userFakultas) {
                    return isset($detail['fakultas']) && $detail['fakultas'] == $userFakultas;
                });
            });
        }

        // Apply status filter to the paginated collection
        if ($selectedStatus && $selectedStatus !== 'all') {
            $kriteriaDokumenCollection = $kriteriaDokumenCollection->filter(function ($item) use ($selectedStatus) {
                return $item->filtered_details && $item->filtered_details->contains(function ($detail) use ($selectedStatus) {
                    return isset($detail['status']) && $detail['status'] === $selectedStatus;
                });
            });
        }

        // Re-create paginator with the filtered collection
        $kriteriaDokumen = new \Illuminate\Pagination\LengthAwarePaginator(
            $kriteriaDokumenCollection->values(), // Reset keys
            $kriteriaDokumenCollection->count(), // New total count
            $perPage,
            $kriteriaDokumenPaginator->currentPage(),
            ['path' => $kriteriaDokumenPaginator->path(), 'query' => $request->query()]
        );

        // Dapatkan semua nilai status yang unik untuk dropdown
        $statusOptions = $allStatuses->unique()->values()->all();

        return view('pemenuhan-dokumen.index', compact(
            'kriteriaDokumen', 
            'statusOptions', 
            'yearOptions',
            'jenjangOptions',
            'selectedStatus',
            'selectedYear',
            'selectedJenjang'
        ));
    }
    


public function showGroup($lembagaId, $jenjangId)
{
    try {
        $user = Auth::user();
        $selectedProdi = request('prodi');
        
        // PRESERVE: Original filter parameter extraction - used by other menus
        $filterStatus = request('status');
        $filterYear = request('year');
        $filterJenjang = request('jenjang');

        // PRESERVE: Original service call - critical for other menus  
        $data = $this->pemenuhanDokumenService->getShowGroupData(
            $lembagaId,
            $jenjangId,
            $selectedProdi,
            $user
        );

        // PRESERVE: Original prodi list filtering logic - other menus depend on this
        $filteredProdiList = $data['prodiList'];
        
        if ($filterStatus && $filterStatus !== 'all') {
            $filteredProdiList = $filteredProdiList->filter(function ($prodi) use ($filterStatus) {
                return isset($prodi['status']) && $prodi['status'] === $filterStatus;
            });
        }
        
        // PRESERVE: Original filter params structure - used by other views
        $filterParams = [
            'status' => $filterStatus,
            'year' => $filterYear,
            'jenjang' => $filterJenjang
        ];

        // PRESERVE: Original calculations that were in controller - maintain compatibility
        $totalKebutuhanSum = 0;
        $totalCapaianSum = 0;
        $statusDokumen = false;
        
        foreach ($data['kriteriaDokumen'] as $categoryName => $categoryCollection) {
            if (empty($categoryName)) continue;
            
            foreach ($categoryCollection as $kriteriaDokumenItem) {
                $totalKebutuhanSum += $kriteriaDokumenItem->total_kebutuhan;
                $totalCapaianSum += $kriteriaDokumenItem->capaian_dokumen;
                
                $statusDokumen = ($kriteriaDokumenItem->capaian_dokumen != $kriteriaDokumenItem->total_kebutuhan) ? false : true;
            }
        }

        // PRESERVE: Original view data structure - critical for backward compatibility
        return view('pemenuhan-dokumen.show-group', [
            // Original required data - DO NOT CHANGE ORDER OR NAMES
            'kriteriaDokumen' => $data['kriteriaDokumen'],
            'statusDokumen' => $data['statusDokumen'] ?? $statusDokumen, // Fallback to original calculation
            'headerData' => $data['headerData'],
            'prodiList' => $filteredProdiList,
            'lembagaId' => $lembagaId,
            'jenjangId' => $jenjangId,
            'selectedProdi' => $selectedProdi,
            'penilaianStatus' => $data['penilaianStatus'] ?? null,
            'filterParams' => $filterParams,
            
            // NEW: Additional pre-calculated data from service (optional - won't break existing)
            'currentProdiData' => $data['currentProdiData'] ?? [],
            'jadwalData' => $data['jadwalData'] ?? [],
            'penilaianData' => $data['penilaianData'] ?? collect(),
            'roleData' => $data['roleData'] ?? [],
            'allCriteriaMet' => $data['allCriteriaMet'] ?? true
        ]);
    } catch (\Exception $e) {
        // PRESERVE: Original error handling
        return redirect()->route('pemenuhan-dokumen.index')
            ->with('error', $e->getMessage());
    }
}

    public function create($kriteriaDokumenId)
    {
        $kriteriaDokumen = KriteriaDokumen::findOrFail($kriteriaDokumenId);
        $kebutuhanDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->get();

        return view('pemenuhan-dokumen.create', compact('kriteriaDokumen', 'kebutuhanDokumen'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kriteria_dokumen_id' => 'required|exists:kriteria_dokumen,id',
            'nama_dokumen' => 'required',
            'file' => 'required|file|max:2048',
            'tambahan_informasi' => 'nullable|string'
        ]);

        $user = Auth::user();
        $kriteriaDokumen = KriteriaDokumen::findOrFail($request->kriteria_dokumen_id);

        // Get kebutuhan dokumen info
        $kebutuhanDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $request->kriteria_dokumen_id)
            ->where('nama_dokumen', $request->nama_dokumen)
            ->first();

        if (!$kebutuhanDokumen) {
            return back()->with('error', 'Dokumen tidak ditemukan dalam daftar kebutuhan.');
        }

        // Check if user has reached the document limit
        $existingDocs = PemenuhanDokumen::where('kriteria_dokumen_id', $request->kriteria_dokumen_id)->count();
        if ($existingDocs >= $kriteriaDokumen->kebutuhan_dokumen) {
            return back()->with('error', 'Batas maksimal dokumen telah tercapai.');
        }

        // Handle file upload
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/pemenuhan_dokumen'), $fileName);

        PemenuhanDokumen::create([
            'kriteria_dokumen_id' => $request->kriteria_dokumen_id,
            'kriteria' => $kriteriaDokumen->judul_kriteria_dokumen->nama_kriteria_dokumen,
            'element' => $kriteriaDokumen->element,
            'indikator' => $kriteriaDokumen->indikator,
            'nama_dokumen' => $request->nama_dokumen,
            'tipe_dokumen' => $kebutuhanDokumen->tipe_dokumen,
            'keterangan' => $kebutuhanDokumen->keterangan,
            'file' => $fileName,
            'periode' => $kriteriaDokumen->periode_atau_tahun,
            'tambahan_informasi' => $request->tambahan_informasi,
            'prodi' => $user->prodi,
            'fakultas' => $user->fakultas
        ]);

        // Log aktivitas
        ActivityLogService::log(
            'created',
            'pemenuhan_dokumen',
            'Created new dokumen: ' . $request->nama_dokumen,
            $request,
            null,
            $request->fresh()->toArray()
        );

        return redirect()->back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function ajukan(Request $request, $lembagaId, $jenjangId)
    {
        try {
            $selectedProdi = $request->prodi;
            if (!$selectedProdi) {
                throw new \Exception('Prodi harus dipilih');
            }

            // Dapatkan kriteria_dokumen_id berdasarkan lembagaId dan jenjangId
            $kriteriaDokumen = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->get();

            // Update status menjadi 'diajukan' di tabel penilaian_kriteria
            foreach ($kriteriaDokumen as $kd) {
                $penilaian = PenilaianKriteria::firstOrNew([
                    'kriteria_dokumen_id' => $kd->id,
                    'prodi' => $selectedProdi
                ]);

                $penilaian->fakultas = getActiveFakultas();
                $penilaian->periode_atau_tahun = $kd->periode_atau_tahun;
                $penilaian->status = PenilaianKriteria::STATUS_DIAJUKAN;
                $penilaian->save();
            }

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'penilaian_kriteria',
                'Status semua dokumen diubah menjadi DIAJUKAN untuk Prodi: ' . $selectedProdi,
                null,
                ['lembaga_id' => $lembagaId, 'jenjang_id' => $jenjangId, 'prodi' => $selectedProdi],
                ['status' => PenilaianKriteria::STATUS_DIAJUKAN]
            );

            return redirect()->back()->with('success', 'Dokumen berhasil diajukan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $lembagaId, $jenjangId)
    {
        try {
            $selectedProdi = $request->prodi;
            $newStatus = $request->status;

            if (!$selectedProdi || !$newStatus) {
                throw new \Exception('Prodi dan status harus diisi');
            }

            // Dapatkan kriteria_dokumen_id berdasarkan lembagaId dan jenjangId
            $kriteriaDokumen = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->get();

            // Update status untuk semua dokumen yang terkait di penilaian_kriteria
            $updated = 0;
            foreach ($kriteriaDokumen as $kd) {
                $penilaian = PenilaianKriteria::firstOrNew([
                    'kriteria_dokumen_id' => $kd->id,
                    'prodi' => $selectedProdi
                ]);

                $penilaian->fakultas = getActiveFakultas();
                $penilaian->periode_atau_tahun = $kd->periode_atau_tahun;
                $penilaian->status = $newStatus;
                $penilaian->save();

                $updated++;
            }

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'penilaian_kriteria',
                'Status semua dokumen diubah menjadi ' . strtoupper($newStatus) . ' untuk Prodi: ' . $selectedProdi,
                null,
                ['lembaga_id' => $lembagaId, 'jenjang_id' => $jenjangId, 'prodi' => $selectedProdi],
                ['status' => $newStatus]
            );

            if (!$updated) {
                throw new \Exception('Gagal mengupdate status');
            }

            return redirect()->back()->with('success', 'Status berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function showDetail($lembagaId, $jenjangId, Request $request)
    {
        try {
            $selectedProdi = $request->prodi;
    
            if (!$selectedProdi) {
                return redirect()->back()->with('error', 'Program studi harus dipilih');
            }
    
            // Dapatkan kriteria_dokumen_id berdasarkan lembagaId dan jenjangId
            $kriteriaDokumenItems = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->with('judulKriteriaDokumen')->get();
    
            // Ambil semua penilaian untuk prodi tersebut
            $allPenilaian = PenilaianKriteria::whereIn('kriteria_dokumen_id', $kriteriaDokumenItems->pluck('id'))
                ->where('prodi', $selectedProdi)
                ->get();
    
            // Ambil semua pemenuhan dokumen untuk prodi tersebut
            $allPemenuhanDokumen = PemenuhanDokumen::whereIn('kriteria_dokumen_id', $kriteriaDokumenItems->pluck('id'))
                ->where('prodi', $selectedProdi)
                ->get();
    
            // Kelompokkan berdasarkan kriteria
            $penilaianByKriteria = [];
    
            foreach ($kriteriaDokumenItems as $kd) {
                $kriteria = $kd->judulKriteriaDokumen->nama_kriteria_dokumen ?? 'Tidak Diketahui';
    
                // Skip jika kriteria 'Tidak Diketahui'
                if ($kriteria === 'Tidak Diketahui') {
                    continue;
                }
    
                $penilaian = $allPenilaian->firstWhere('kriteria_dokumen_id', $kd->id);
    
                if ($penilaian) {
                    // Reset nilai default
                    $penilaian->nama_dokumen = null;
                    $penilaian->tambahan_informasi = null;
    
                    // Cari data pemenuhan dokumen yang sesuai (bisa ada lebih dari satu)
                    $pemenuhanDokumens = $allPemenuhanDokumen->where('kriteria_dokumen_id', $kd->id)->values();
    
                    if ($pemenuhanDokumens->count() > 0) {
                        // Gunakan data dari pemenuhan dokumen pertama untuk tampilan utama
                        $penilaian->nama_dokumen = $pemenuhanDokumens->first()->nama_dokumen;
                        $penilaian->tambahan_informasi = $pemenuhanDokumens->first()->tambahan_informasi;
                    
                        // Simpan semua file dokumen dan informasi terkait
                        $penilaian->files_dokumen = $pemenuhanDokumens->pluck('file')->toArray();
                        $penilaian->nama_dokumens = $pemenuhanDokumens->pluck('nama_dokumen')->toArray();
                        $penilaian->tipe_dokumens = $pemenuhanDokumens->pluck('tipe_dokumen')->toArray();
                        $penilaian->tambahan_informasis = $pemenuhanDokumens->pluck('tambahan_informasi')->toArray();
                    }
    
                    // Jika masih null, coba cari dari KelolaKebutuhanKriteriaDokumen
                    if ($penilaian->nama_dokumen === null) {
                        $kelolaKebutuhan = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kd->id)
                            ->first();
    
                        if ($kelolaKebutuhan) {
                            $penilaian->nama_dokumen = $kelolaKebutuhan->nama_dokumen;
                        } else {
                            $penilaian->nama_dokumen = '';
                        }
                    }
    
                    if ($penilaian->tambahan_informasi === null) {
                        $penilaian->tambahan_informasi = '';
                    }
    
                    // Isi data element, indikator, dll dari kriteria dokumen
                    $penilaian->kode = $kd->kode;
                    $penilaian->element = $kd->element;
                    $penilaian->indikator = $kd->indikator;
                    
                    // PERUBAHAN: Ambil bobot dari kriteria_dokumen
                    $penilaian->bobot = $kd->bobot ?? $penilaian->bobot;
    
                    if (!isset($penilaianByKriteria[$kriteria])) {
                        $penilaianByKriteria[$kriteria] = collect();
                    }
    
                    $penilaian->kriteriaDokumen = $kd;
                    $penilaianByKriteria[$kriteria]->push($penilaian);
                }
            }
    
            // Dapatkan semua nama kriteria dan urutkan secara numerik
            $kriteriaNames = array_keys($penilaianByKriteria);
    
            // Urutkan kriteria berdasarkan nomor kriteria
            usort($kriteriaNames, function ($a, $b) {
                // Ekstrak nomor dari nama kriteria (misal: "Kriteria 1" -> 1)
                $numA = (int) preg_replace('/[^0-9]/', '', $a);
                $numB = (int) preg_replace('/[^0-9]/', '', $b);
    
                return $numA - $numB;
            });
    
            // Buat array penilaian yang sudah diurutkan
            $sortedDokumenDetail = [];
            foreach ($kriteriaNames as $kriteria) {
                $sortedDokumenDetail[$kriteria] = $penilaianByKriteria[$kriteria];
            }
    
            // Perhitungan total untuk setiap kriteria
            $totalByKriteria = [];
            foreach ($sortedDokumenDetail as $kriteria => $penilaians) {
                $totalNilai = $penilaians->sum('nilai');
                $totalBobot = $penilaians->sum('bobot');
                $totalTertimbang = $penilaians->sum('tertimbang');
                $totalNilaiAuditor = $penilaians->whereNotNull('nilai_auditor')->sum('nilai_auditor');
                
                // Hitung rata-rata nilai jika ada penilaian
                $count = $penilaians->count();
                $avgNilai = $count > 0 ? $totalNilai / $count : 0;
                
                // PERUBAHAN: Tentukan sebutan berdasarkan rata-rata nilai
                $sebutan = $this->getSebutan($avgNilai);
                
                $totalByKriteria[$kriteria] = [
                    'nilai' => $avgNilai,
                    'bobot' => $totalBobot,
                    'tertimbang' => $totalTertimbang,
                    'nilai_auditor' => $totalNilaiAuditor,
                    'sebutan' => $sebutan
                ];
            }
    
            // Perhitungan total kumulatif untuk semua kriteria
            $totalKumulatif = [
                'nilai' => 0,
                'bobot' => 0,
                'tertimbang' => 0,
                'nilai_auditor' => 0
            ];

            // PERUBAHAN: Hanya jumlahkan nilai, tidak dirata-ratakan
            foreach ($totalByKriteria as $kriteria => $totals) {
                // Jumlahkan total nilai capaian dari setiap kriteria
                $totalKumulatif['nilai'] += $sortedDokumenDetail[$kriteria]->sum('nilai');
                $totalKumulatif['bobot'] += $totals['bobot'];
                $totalKumulatif['tertimbang'] += $totals['tertimbang'];
                $totalKumulatif['nilai_auditor'] += $totals['nilai_auditor'];
            }

            // PERUBAHAN: Jika ada nilai kumulatif, tentukan sebutannya
            if ($totalKumulatif['nilai'] > 0) {
                // Tidak perlu membagi dengan jumlah kriteria lagi
                $totalKumulatif['sebutan'] = $this->getSebutan($totalKumulatif['nilai'] / count($sortedDokumenDetail));
            } else {
                $totalKumulatif['sebutan'] = '-';
            }
    
            // Ambil informasi header
            $headerData = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->first();
    
            return view('pemenuhan-dokumen.detail', [
                'dokumenDetail' => $sortedDokumenDetail,
                'totalByKriteria' => $totalByKriteria,
                'totalKumulatif' => $totalKumulatif,
                'headerData' => $headerData,
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function getSebutan($nilai)
    {
        if ($nilai == 4) {
            return 'Sangat Baik';
        } elseif ($nilai >= 3 && $nilai < 4) {
            return 'Baik';
        } elseif ($nilai >= 2 && $nilai < 3) {
            return 'Cukup';
        } elseif ($nilai >= 1 && $nilai < 2) {
            return 'Kurang';
        } elseif ($nilai >= 0 && $nilai < 1) {
            return 'Sangat Kurang';
        } else {
            return '-';
        }
    }

    public function exportPdf($lembagaId, $jenjangId, Request $request)
    {
        try {
            $selectedProdi = $request->prodi;

            if (!$selectedProdi) {
                return redirect()->back()->with('error', 'Program studi harus dipilih');
            }

            // Gunakan data yang sama dengan showDetail
            $data = $this->prepareDetailData($lembagaId, $jenjangId, $selectedProdi);

            // TAMBAHAN: Convert logo ke base64
            $logoPath = public_path('img/picture_logo.png');
            $logoBase64 = '';
            
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $logoPath);
                finfo_close($finfo);
                
                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
            }
            
            // Tambahkan logo ke data
            $data['logoBase64'] = $logoBase64;

            // Gunakan library PDF seperti DomPDF
            $pdf = \PDF::loadView('pemenuhan-dokumen.detail-pdf', $data);

            // Set paper size to A4 landscape
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'margin-top'    => 10,
                'margin-right'  => 10,
                'margin-bottom' => 10,
                'margin-left'   => 10,
            ]);
            
            // Generate filename
            $filename = 'Pemenuhan_Dokumen_' . str_replace(' ', '_', $selectedProdi) . '_' . date('Y-m-d') . '.pdf';

             // PERUBAHAN: Stream PDF di browser untuk preview
            return $pdf->stream($filename);
            
            // Untuk download langsung, gunakan:
            // return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekspor PDF: ' . $e->getMessage());
        }
    }

    private function prepareDetailData($lembagaId, $jenjangId, $selectedProdi)
    {
        // Dapatkan kriteria_dokumen_id berdasarkan lembagaId dan jenjangId
        $kriteriaDokumenItems = KriteriaDokumen::where([
            'lembaga_akreditasi_id' => $lembagaId,
            'jenjang_id' => $jenjangId
        ])->with('judulKriteriaDokumen')->get();
    
        // Query untuk mendapatkan semua penilaian
        $allPenilaian = PenilaianKriteria::whereIn('kriteria_dokumen_id', $kriteriaDokumenItems->pluck('id'))
            ->where('prodi', $selectedProdi)
            ->get();
    
        // Ambil semua pemenuhan dokumen untuk prodi tersebut
        $allPemenuhanDokumen = PemenuhanDokumen::whereIn('kriteria_dokumen_id', $kriteriaDokumenItems->pluck('id'))
            ->where('prodi', $selectedProdi)
            ->get();
    
        // Kelompokkan berdasarkan kriteria
        $penilaianByKriteria = [];
    
        foreach ($kriteriaDokumenItems as $kd) {
            $kriteria = $kd->judulKriteriaDokumen->nama_kriteria_dokumen ?? 'Tidak Diketahui';
    
            // Skip jika kriteria 'Tidak Diketahui'
            if ($kriteria === 'Tidak Diketahui') {
                continue;
            }
    
            $penilaian = $allPenilaian->firstWhere('kriteria_dokumen_id', $kd->id);
    
            if ($penilaian) {
                // Reset nilai default
                $penilaian->nama_dokumen = null;
                $penilaian->tambahan_informasi = null;
    
                // Cari data pemenuhan dokumen yang sesuai (bisa ada lebih dari satu)
                $pemenuhanDokumens = $allPemenuhanDokumen->where('kriteria_dokumen_id', $kd->id)->values();
    
                if ($pemenuhanDokumens->count() > 0) {
                    // Gunakan data dari pemenuhan dokumen pertama untuk tampilan utama
                    $penilaian->nama_dokumen = $pemenuhanDokumens->first()->nama_dokumen;
                    $penilaian->tambahan_informasi = $pemenuhanDokumens->first()->tambahan_informasi;
    
                    // Simpan semua file dokumen dan informasi terkait
                    $penilaian->files_dokumen = $pemenuhanDokumens->pluck('file')->toArray();
                    $penilaian->nama_dokumens = $pemenuhanDokumens->pluck('nama_dokumen')->toArray();
                    $penilaian->tipe_dokumens = $pemenuhanDokumens->pluck('tipe_dokumen')->toArray();
                    $penilaian->tambahan_informasis = $pemenuhanDokumens->pluck('tambahan_informasi')->toArray();
                }
    
                // Jika masih null, coba cari dari KelolaKebutuhanKriteriaDokumen
                if ($penilaian->nama_dokumen === null) {
                    $kelolaKebutuhan = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kd->id)
                        ->first();
    
                    if ($kelolaKebutuhan) {
                        $penilaian->nama_dokumen = $kelolaKebutuhan->nama_dokumen;
                    } else {
                        $penilaian->nama_dokumen = '';
                    }
                }
    
                if ($penilaian->tambahan_informasi === null) {
                    $penilaian->tambahan_informasi = '';
                }
    
                // Isi data element, indikator, dll dari kriteria dokumen
                $penilaian->kode = $kd->kode;
                $penilaian->element = $kd->element;
                $penilaian->indikator = $kd->indikator;
                
                // PERUBAHAN: Ambil bobot dari kriteria_dokumen
                $penilaian->bobot = $kd->bobot ?? $penilaian->bobot;
    
                if (!isset($penilaianByKriteria[$kriteria])) {
                    $penilaianByKriteria[$kriteria] = collect();
                }
    
                $penilaian->kriteriaDokumen = $kd;
                $penilaianByKriteria[$kriteria]->push($penilaian);
            }
        }
    
        // Dapatkan semua nama kriteria dan urutkan secara numerik
        $kriteriaNames = array_keys($penilaianByKriteria);
    
        // Urutkan kriteria berdasarkan nomor kriteria
        usort($kriteriaNames, function ($a, $b) {
            // Ekstrak nomor dari nama kriteria (misal: "Kriteria 1" -> 1)
            $numA = (int) preg_replace('/[^0-9]/', '', $a);
            $numB = (int) preg_replace('/[^0-9]/', '', $b);
    
            return $numA - $numB;
        });
    
        // Buat array penilaian yang sudah diurutkan
        $sortedDokumenDetail = [];
        foreach ($kriteriaNames as $kriteria) {
            $sortedDokumenDetail[$kriteria] = $penilaianByKriteria[$kriteria];
        }
    
        // Perhitungan total untuk setiap kriteria
        $totalByKriteria = [];
        foreach ($sortedDokumenDetail as $kriteria => $penilaians) {
            $totalNilai = $penilaians->sum('nilai');
            $totalBobot = $penilaians->sum('bobot');
            $totalTertimbang = $penilaians->sum('tertimbang');
            $totalNilaiAuditor = $penilaians->whereNotNull('nilai_auditor')->sum('nilai_auditor');
            
            // Hitung rata-rata nilai jika ada penilaian
            $count = $penilaians->count();
            $avgNilai = $count > 0 ? $totalNilai / $count : 0;
            
            // PERUBAHAN: Tentukan sebutan berdasarkan rata-rata nilai
            $sebutan = $this->getSebutan($avgNilai);
            
            $totalByKriteria[$kriteria] = [
                'nilai' => $avgNilai,
                'bobot' => $totalBobot,
                'tertimbang' => $totalTertimbang,
                'nilai_auditor' => $totalNilaiAuditor,
                'sebutan' => $sebutan
            ];
        }
    
        // Perhitungan total kumulatif untuk semua kriteria
        $totalKumulatif = [
            'nilai' => 0,
            'bobot' => 0,
            'tertimbang' => 0,
            'nilai_auditor' => 0
        ];

        // PERUBAHAN: Hanya jumlahkan nilai, tidak dirata-ratakan
        foreach ($totalByKriteria as $kriteria => $totals) {
            // Jumlahkan total nilai capaian dari setiap kriteria
            $totalKumulatif['nilai'] += $sortedDokumenDetail[$kriteria]->sum('nilai');
            $totalKumulatif['bobot'] += $totals['bobot'];
            $totalKumulatif['tertimbang'] += $totals['tertimbang'];
            $totalKumulatif['nilai_auditor'] += $totals['nilai_auditor'];
        }

        // PERUBAHAN: Jika ada nilai kumulatif, tentukan sebutannya
        if ($totalKumulatif['nilai'] > 0) {
            // Tidak perlu membagi dengan jumlah kriteria lagi
            $totalKumulatif['sebutan'] = $this->getSebutan($totalKumulatif['nilai'] / count($sortedDokumenDetail));
        } else {
            $totalKumulatif['sebutan'] = '-';
        }
    
        // Ambil informasi header
        $headerData = KriteriaDokumen::where([
            'lembaga_akreditasi_id' => $lembagaId,
            'jenjang_id' => $jenjangId
        ])->first();
    
        return [
            'dokumenDetail' => $sortedDokumenDetail,
            'totalByKriteria' => $totalByKriteria,
            'totalKumulatif' => $totalKumulatif,
            'headerData' => $headerData,
            'lembagaId' => $lembagaId,
            'jenjangId' => $jenjangId,
            'selectedProdi' => $selectedProdi
        ];
    }

    /**
     * Mengambil daftar jadwal AMI yang relevan berdasarkan peran pengguna.
     * Digunakan untuk memfilter detail kriteria dokumen.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    private function getJadwalAmiListForFiltering($user)
    {
        $jadwalAmiRepo = app('App\\Repositories\\PemenuhanDokumen\\JadwalAmiRepository');
        $pemenuhanDokumenService = app('App\\Services\\PemenuhanDokumen\\PemenuhanDokumenService');

        if ($user->hasActiveRole('Super Admin') || $user->hasActiveRole('Admin LPM')) {
            $jadwalAmiList = $jadwalAmiRepo->getActiveJadwal();
        } elseif ($user->hasActiveRole('Fakultas')) {
            $jadwalAmiList = $jadwalAmiRepo->getActiveJadwal();
            // Note: The 'fakultas' filter was applied to $filters in service,
            // but here we need to filter the list directly if it's not handled by the main query.
            // For now, we assume main query handles it.
        } else {
            $jadwalAmiList = $jadwalAmiRepo->getActiveJadwal($user->prodi);
        }

        // For auditor, filter jadwal list
        if ($user->hasActiveRole('Auditor')) {
            $jadwalAmiList = $jadwalAmiList->filter(function($jadwal) use ($user) {
                return $jadwal->timAuditor->pluck('id')->contains($user->id);
            });
            // The activeSchedules mapping was used to build filters for the main query.
            // Here, we just need the filtered jadwalAmiList.
        }

        return $jadwalAmiList;
    }
}
