<?php

namespace App\Http\Controllers;

use App\Models\PenilaianKriteria;
use App\Models\KriteriaDokumen;
use App\Models\JadwalAmi;
use App\Models\KelolaKebutuhanKriteriaDokumen;
use App\Models\PemenuhanDokumen;
use App\Models\Jenjang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\PemenuhanDokumen\PemenuhanDokumenService;
use Illuminate\Pagination\LengthAwarePaginator;

class ForecastingController extends Controller
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
        $kriteriaDokumen = $this->pemenuhanDokumenService->getFilteredData($filters, $perPage);
        
        // Dapatkan semua nilai status yang unik untuk dropdown
        $statusOptions = $this->getUniqueStatuses($kriteriaDokumen);
        
        // Dapatkan semua tahun yang unik
        $yearOptions = $this->getUniqueYears($kriteriaDokumen);
        
        // Dapatkan semua jenjang dari model Jenjang
        $jenjangOptions = Jenjang::pluck('nama', 'id')->toArray();
        
        // Filter status di sisi controller setelah data diambil dari service
        $selectedStatus = $request->get('status');
        $selectedYear = $request->get('year');
        $selectedJenjang = $request->get('jenjang');
        
        if ($selectedStatus && $selectedStatus !== 'all') {
            // Filter collection berdasarkan status yang dipilih
            $kriteriaDokumen = $this->filterByStatus($kriteriaDokumen, $selectedStatus);
        }
        
        if ($selectedYear && $selectedYear !== 'all') {
            // Filter collection berdasarkan tahun yang dipilih
            $kriteriaDokumen = $this->filterByYear($kriteriaDokumen, $selectedYear);
        }
        
        if ($selectedJenjang && $selectedJenjang !== 'all') {
            // Filter collection berdasarkan jenjang yang dipilih
            $kriteriaDokumen = $this->filterByJenjang($kriteriaDokumen, $selectedJenjang);
        }
        
        return view('forecasting.index', compact(
            'kriteriaDokumen', 
            'statusOptions', 
            'yearOptions',
            'jenjangOptions',
            'selectedStatus',
            'selectedYear',
            'selectedJenjang'
        ));
    }

    /**
     * Mendapatkan semua nilai status yang unik dari dokumen
     */
    private function getUniqueStatuses($kriteriaDokumen)
    {
        $statuses = collect();
        
        foreach ($kriteriaDokumen as $item) {
            foreach ($item->filtered_details as $detail) {
                if (isset($detail['status'])) {
                    $statuses->push($detail['status']);
                }
            }
        }
        
        return $statuses->unique()->values()->all();
    }
    
    /**
     * Mendapatkan semua tahun unik dari dokumen
     */
    private function getUniqueYears($kriteriaDokumen)
    {
        $years = collect();
        
        foreach ($kriteriaDokumen as $item) {
            if (isset($item->periode_atau_tahun)) {
                $years->push($item->periode_atau_tahun);
            }
        }
        
        return $years->unique()->values()->all();
    }
    
    /**
     * Filter collection berdasarkan status
     */
    private function filterByStatus($kriteriaDokumen, $status)
    {
        // Kita perlu mengkloning collection untuk menghindari perubahan pada collection asli
        $filteredCollection = new LengthAwarePaginator(
            $kriteriaDokumen->getCollection()->filter(function ($item) use ($status) {
                // Filter detail berdasarkan status
                $filteredDetails = $item->filtered_details->filter(function ($detail) use ($status) {
                    return isset($detail['status']) && $detail['status'] === $status;
                });
                
                // Jika setelah filtering masih ada detail, simpan dan update filtered_details
                if ($filteredDetails->isNotEmpty()) {
                    $item->filtered_details = $filteredDetails;
                    return true;
                }
                
                return false;
            }),
            $kriteriaDokumen->total(), // Total asli dari paginator
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );
        
        return $filteredCollection;
    }
    
    /**
     * Filter collection berdasarkan tahun
     */
    private function filterByYear($kriteriaDokumen, $year)
    {
        // Filter berdasarkan tahun
        $filteredCollection = new LengthAwarePaginator(
            $kriteriaDokumen->getCollection()->filter(function ($item) use ($year) {
                return isset($item->periode_atau_tahun) && $item->periode_atau_tahun == $year;
            }),
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );
        
        return $filteredCollection;
    }
    
    /**
     * Filter collection berdasarkan jenjang
     */
    private function filterByJenjang($kriteriaDokumen, $jenjangNama)
    {
        // Filter berdasarkan jenjang
        $filteredCollection = new LengthAwarePaginator(
            $kriteriaDokumen->getCollection()->filter(function ($item) use ($jenjangNama) {
                return isset($item->jenjang) && $item->jenjang->nama === $jenjangNama;
            }),
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );
        
        return $filteredCollection;
    }

    public function showGroup($lembagaId, $jenjangId)
    {
        try {
            $user = Auth::user();
            $selectedProdi = request('prodi');
            
            // Dapatkan parameter filter dari request
            $filterStatus = request('status');
            $filterYear = request('year');
            $filterJenjang = request('jenjang');

            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                $user
            );

            // Filter daftar prodi berdasarkan parameter yang diteruskan dari halaman index
            $filteredProdiList = $data['prodiList'];
            
            if ($filterStatus && $filterStatus !== 'all') {
                $filteredProdiList = $filteredProdiList->filter(function ($prodi) use ($filterStatus) {
                    return isset($prodi['status']) && $prodi['status'] === $filterStatus;
                });
            }
            
            // Tampilkan parameter filter di view untuk debugging jika diperlukan
            $filterParams = [
                'status' => $filterStatus,
                'year' => $filterYear,
                'jenjang' => $filterJenjang
            ];
            
            // Simpan filter params untuk digunakan di halaman lain
            session(['filter_params' => $filterParams]);

            // Cek status penilaian untuk prodi yang dipilih
            $penilaianStatus = null;
            if ($selectedProdi) {
                $penilaian = PenilaianKriteria::where('prodi', $selectedProdi)
                    ->whereHas('kriteriaDokumen', function ($query) use ($lembagaId, $jenjangId) {
                        $query->where('lembaga_akreditasi_id', $lembagaId)
                            ->where('jenjang_id', $jenjangId);
                    })
                    ->first();

                $penilaianStatus = $penilaian ? $penilaian->status : PenilaianKriteria::STATUS_DRAFT;
                
                // Prepare detail data when prodi is selected
                $detailData = $this->prepareDetailData($lembagaId, $jenjangId, $selectedProdi);
                $dokumenDetail = $detailData['dokumenDetail'];
                $totalByKriteria = $detailData['totalByKriteria'];
                $totalKumulatif = $detailData['totalKumulatif'];
            } else {
                // Empty data when no prodi is selected
                $dokumenDetail = [];
                $totalByKriteria = [];
                $totalKumulatif = [];
            }

            // Add periode_atau_tahun to prodiList
            foreach ($data['prodiList'] as &$prodi) {
                $prodi['prodi_dengan_tahun'] = $prodi['prodi'] . ' - ' . ($data['headerData']->periode_atau_tahun ?? date('Y'));
            }

            return view('forecasting.show-group', [
                'dokumenDetail' => $dokumenDetail,
                'totalByKriteria' => $totalByKriteria,
                'totalKumulatif' => $totalKumulatif,
                'headerData' => $data['headerData'],
                'prodiList' => $filteredProdiList,
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi,
                'penilaianStatus' => $penilaianStatus,
                'filterParams' => $filterParams
            ]);
        } catch (\Exception $e) {
            return redirect()->route('forecasting.index')
                ->with('error', $e->getMessage());
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

            // Generate PDF
            $pdf = \PDF::loadView('forecasting.detail-pdf', [
                'dokumenDetail' => $data['dokumenDetail'],
                'totalByKriteria' => $data['totalByKriteria'],
                'totalKumulatif' => $data['totalKumulatif'],
                'headerData' => KriteriaDokumen::where(['lembaga_akreditasi_id' => $lembagaId, 'jenjang_id' => $jenjangId])->first(),
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi,
                'filterParams' => session('filter_params', [])
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
            $filename = 'Forecasting_' . str_replace(' ', '_', $selectedProdi) . '_' . date('Y-m-d') . '.pdf';

             // PERUBAHAN: Stream PDF di browser untuk preview
            return $pdf->stream($filename);
            
            // Untuk download langsung, gunakan:
            // return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekspor PDF: ' . $e->getMessage());
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

    private function prepareDetailData($lembagaId, $jenjangId, $selectedProdi)
    {
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

            // PERBAIKAN: Cek apakah penilaian ada, jika tidak buat dummy penilaian
            $penilaian = $allPenilaian->firstWhere('kriteria_dokumen_id', $kd->id);
            
            // PERBAIKAN: Jika tidak ada penilaian, buat objek dummy untuk menampilkan data
            if (!$penilaian) {
                $penilaian = new PenilaianKriteria([
                    'kriteria_dokumen_id' => $kd->id,
                    'prodi' => $selectedProdi,
                    'nilai' => 0,
                    'bobot' => $kd->bobot ?? 0,
                    'tertimbang' => 0,
                    'nilai_auditor' => null,
                    'sebutan' => '-'
                ]);
                // Set ID dummy untuk konsistensi
                $penilaian->id = 'dummy_' . $kd->id;
                $penilaian->exists = false; // Tandai sebagai dummy object
            }

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

            // PERBAIKAN: Selalu cek dari KelolaKebutuhanKriteriaDokumen untuk memastikan data tampil
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
            
            // Ambil bobot dari kriteria_dokumen
            $penilaian->bobot = $kd->bobot ?? $penilaian->bobot;

            if (!isset($penilaianByKriteria[$kriteria])) {
                $penilaianByKriteria[$kriteria] = collect();
            }

            $penilaian->kriteriaDokumen = $kd;
            $penilaianByKriteria[$kriteria]->push($penilaian);
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
            
            // Tentukan sebutan berdasarkan rata-rata nilai
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

        // Hanya jumlahkan nilai, tidak dirata-ratakan
        foreach ($totalByKriteria as $kriteria => $totals) {
            // Jumlahkan total nilai capaian dari setiap kriteria
            $totalKumulatif['nilai'] += $sortedDokumenDetail[$kriteria]->sum('nilai');
            $totalKumulatif['bobot'] += $totals['bobot'];
            $totalKumulatif['tertimbang'] += $totals['tertimbang'];
            $totalKumulatif['nilai_auditor'] += $totals['nilai_auditor'];
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

    public function visualize($lembagaId, $jenjangId, Request $request)
    {
        try {
            $selectedProdi = $request->prodi;

            if (!$selectedProdi) {
                return redirect()->route('forecasting.showGroup', [
                    'lembagaId' => $lembagaId, 
                    'jenjangId' => $jenjangId,
                    'status' => request('status'),
                    'year' => request('year'),
                    'jenjang' => request('jenjang')
                ])->with('error', 'Program studi harus dipilih untuk visualisasi');
            }

            // Ambil data yang sama dengan showGroup
            $detailData = $this->prepareDetailData($lembagaId, $jenjangId, $selectedProdi);
            $dokumenDetail = $detailData['dokumenDetail'];
            $totalByKriteria = $detailData['totalByKriteria'];
            $totalKumulatif = $detailData['totalKumulatif'];
            
            // Persiapkan data untuk grafik
            $chartData = [];
            
            foreach ($dokumenDetail as $kriteria => $documents) {
                $kriteriaSeries = [];
                $kriteriaLabels = [];
                
                foreach ($documents as $dokumen) {
                    $kriteriaLabels[] = $dokumen->kode;
                    $kriteriaSeries[] = $dokumen->nilai ?? 0;
                }
                
                $chartData[$kriteria] = [
                    'labels' => $kriteriaLabels,
                    'series' => $kriteriaSeries
                ];
            }
            
            // Ambil filter params dari session
            $filterParams = session('filter_params', []);
            
            // Ambil informasi header untuk menampilkan nama prodi dan periode
            $headerData = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->with(['lembagaAkreditasi', 'jenjang'])->first();

            return view('forecasting.visualize', [
                'dokumenDetail' => $dokumenDetail,
                'totalByKriteria' => $totalByKriteria,
                'totalKumulatif' => $totalKumulatif,
                'chartData' => $chartData,
                'headerData' => $headerData,
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi,
                'filterParams' => $filterParams
            ]);
        } catch (\Exception $e) {
            return redirect()->route('forecasting.showGroup', [
                'lembagaId' => $lembagaId, 
                'jenjangId' => $jenjangId,
                'status' => request('status'),
                'year' => request('year'),
                'jenjang' => request('jenjang')
            ])->with('error', $e->getMessage());
        }
    }
}