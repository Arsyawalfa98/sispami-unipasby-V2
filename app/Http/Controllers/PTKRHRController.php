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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PTKRHRController extends Controller
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

        return view('ptkrhr.index', compact(
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

                // // Dump and die untuk melihat struktur data yang tersedia
                // dd([
                //     'detailData' => $detailData,
                //     'data' => $data,
                //     'filteredProdiList' => $filteredProdiList,
                //     'headerData' => $data['headerData'],
                //     'penilaianStatus' => $penilaianStatus
                // ]);

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

            return view('ptkrhr.show-group', [
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
            return redirect()->route('ptkrhr.index')
                ->with('error', $e->getMessage());
        }
    }

    // Method lainnya tidak diperlukan saat ini karena fokus hanya pada menampilkan data di index dan show-group

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

            // PERUBAHAN: Cek apakah penilaian ada, jika tidak buat dummy penilaian
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
                    'sebutan' => '-',
                    // Data PTK default
                    'tanggal_pemenuhan' => null,
                    'status_temuan' => null,
                    'hasil_ami' => null,
                    'output' => null,
                    'akar_penyebab_masalah' => null,
                    'tinjauan_efektivitas_koreksi' => null,
                    'kesimpulan' => null
                ]);
                // Set ID dummy untuk export functions
                $penilaian->id = 'dummy_' . $kd->id;
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

            // PERBAIKAN: Logic penanggung jawab tetap menggunakan 2 kemungkinan
            $penilaian->penanggung_jawab = $this->getPenanggungJawab($penilaian, $selectedProdi);

            // Isi data element, indikator, dll dari kriteria dokumen
            $penilaian->kode = $kd->kode;
            $penilaian->element = $kd->element;
            $penilaian->indikator = $kd->indikator;

            // Ambil bobot dari kriteria_dokumen
            $penilaian->bobot = $kd->bobot ?? $penilaian->bobot;

            // PENAMBAHAN BARU: DATA DOKUMEN UNTUK PTKRHR
            // 1. Total kebutuhan dokumen (dari KelolaKebutuhanKriteriaDokumen)
            $totalKebutuhan = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kd->id)->count();
            $penilaian->total_kebutuhan = $totalKebutuhan;

            // 2. Capaian dokumen (jumlah dokumen yang sudah di-upload)
            $capaianDokumen = $pemenuhanDokumens->count();
            $penilaian->capaian_dokumen = $capaianDokumen;

            // 3. Status dokumen lengkap untuk badge Ada/Tidak Ada
            $penilaian->status_dokumen_lengkap = ($capaianDokumen >= $totalKebutuhan && $totalKebutuhan > 0);

            if (!isset($penilaianByKriteria[$kriteria])) {
                $penilaianByKriteria[$kriteria] = collect();
            }

            $penilaian->kriteriaDokumen = $kd;
            $penilaianByKriteria[$kriteria]->push($penilaian);
        }

        // Rest of the method remains the same...
        // Dapatkan semua nama kriteria dan urutkan secara numerik
        $kriteriaNames = array_keys($penilaianByKriteria);

        // Urutkan kriteria berdasarkan nomor kriteria
        usort($kriteriaNames, function ($a, $b) {
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

            $count = $penilaians->count();
            $avgNilai = $count > 0 ? $totalNilai / $count : 0;

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

        foreach ($totalByKriteria as $kriteria => $totals) {
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

    private function getPenanggungJawab($penilaian, $selectedProdi)
    {
        // KEMUNGKINAN 1: Cek dari database
        if (!empty($penilaian->penanggung_jawab)) {
            return $penilaian->penanggung_jawab;
        }

        // KEMUNGKINAN 2: Logika fallback jika kosong (sama seperti penilaian-kriteria)
        return $this->generatePenanggungJawabFromProdi($selectedProdi);
    }
    
    private function generatePenanggungJawabFromProdi($selectedProdi)
    {
        // Extract kode prodi dari selected prodi
        $kodeProdi = null;
        if ($selectedProdi) {
            // Find the position of the "-"
            $dashPosition = strpos($selectedProdi, '-');

            if ($dashPosition !== false) {
                // Extract the part before the "-" and trim any whitespace
                $kodeProdi = trim(substr($selectedProdi, 0, $dashPosition));
            }
        }

        // Initialize with default
        $namaKaprodi = 'Tidak Tersedia';

        // Special case for Program Profesi
        if ($selectedProdi && stripos($selectedProdi, 'Program Profesi') !== false) {
            $namaKaprodi = 'Erna Puji Astutik, S.Si., M.Pd., M.Sc.';
        } else {
            // Get from Siakad model
            try {
                if (class_exists('\App\Models\Siakad')) {
                    $kaprodiName = \App\Models\Siakad::getKaprodiByKodeUnit($kodeProdi);
                    if ($kaprodiName) {
                        $namaKaprodi = $kaprodiName;
                    }
                }
            } catch (\Exception $e) {
                // Log error atau handle exception jika diperlukan
                \Log::error('Error getting Kaprodi from Siakad: ' . $e->getMessage());
            }
        }

        return $namaKaprodi;
    }
    
    public function generatePdf($lembagaId, $jenjangId, $dokumenId, $prodi, Request $request)
    {
        try {
            // Get additional parameters from request
            $kode = $request->get('kode', '');
            $tanggal = $request->get('tanggal', '');
            $revisi = $request->get('revisi', '');

            // PERBAIKAN: Handle dokumenId 0 atau dummy atau tidak ditemukan
            $dokumen = null;
            $kriteriaDokumen = null;
            
            // Coba cari dokumen normal dulu
            if ($dokumenId > 0) {
                $dokumen = PenilaianKriteria::find($dokumenId);
            }
            
            // Jika tidak ditemukan atau dokumenId = 0, buat data kosong
            if (!$dokumen) {
                // Cari kriteria dokumen untuk data "INI BARU" berdasarkan prodi dan lembaga/jenjang
                $kriteriaDokumenItems = KriteriaDokumen::where([
                    'lembaga_akreditasi_id' => $lembagaId,
                    'jenjang_id' => $jenjangId
                ])->with('judulKriteriaDokumen')->get();
                
                // Ambil kriteria dokumen pertama yang tidak memiliki penilaian untuk prodi ini
                $kriteriaDokumen = null;
                foreach ($kriteriaDokumenItems as $kd) {
                    $existingPenilaian = PenilaianKriteria::where('kriteria_dokumen_id', $kd->id)
                        ->where('prodi', $prodi)
                        ->first();
                        
                    if (!$existingPenilaian) {
                        $kriteriaDokumen = $kd;
                        break;
                    }
                }
                
                // Jika tidak ada yang cocok, ambil yang pertama saja
                if (!$kriteriaDokumen && $kriteriaDokumenItems->isNotEmpty()) {
                    $kriteriaDokumen = $kriteriaDokumenItems->first();
                }
                
                if (!$kriteriaDokumen) {
                    return redirect()->back()->with('error', 'Tidak ada kriteria dokumen yang ditemukan');
                }

                // Buat objek dummy dengan data kosong
                $dokumen = new \stdClass();
                $dokumen->kode = $kriteriaDokumen->kode ?? 'INI BARU';
                $dokumen->hasil_ami = '-';
                $dokumen->akar_penyebab_masalah = '-';
                $dokumen->output = '-';
                $dokumen->tanggal_pemenuhan = null;
                $dokumen->tinjauan_efektivitas_koreksi = '-';
                $dokumen->kesimpulan = '-';
                $dokumen->status_temuan = '';
                $dokumen->fakultas = getActiveFakultas() ?? 'Tidak tersedia';
            } else {
                // Data normal yang ditemukan
                $kriteriaDokumen = KriteriaDokumen::find($dokumen->kriteria_dokumen_id);
                if (!$kriteriaDokumen) {
                    return redirect()->back()->with('error', 'Kriteria dokumen tidak ditemukan');
                }
            }

            // Ambil data lembaga akreditasi dan jenjang
            $lembagaAkreditasi = $kriteriaDokumen->lembagaAkreditasi;
            $jenjang = $kriteriaDokumen->jenjang;

            // Cari data prodi dan auditor dari service
            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $prodi,
                Auth::user()
            );

            // Ambil tim_auditor dari prodiList dengan pemisahan ketua dan anggota
            $auditorKetua = '';
            $auditorAnggota = '';
            if (isset($data['prodiList'])) {
                foreach ($data['prodiList'] as $prodiItem) {
                    if ($prodiItem['prodi'] === $prodi) {
                        if (isset($prodiItem['tim_auditor_detail'])) {
                            $auditorKetua = $prodiItem['tim_auditor_detail']['ketua'] ?? '';
                            $auditorAnggota = isset($prodiItem['tim_auditor_detail']['anggota'])
                                ? implode(', ', $prodiItem['tim_auditor_detail']['anggota'])
                                : '';
                        } else {
                            $auditorAnggota = $prodiItem['tim_auditor'] ?? '';
                        }
                        break;
                    }
                }
            }

            // Jika masih kosong, coba cari dari JadwalAmi
            if (empty($auditorKetua) && empty($auditorAnggota)) {
                $jadwalAmi = JadwalAmi::where('prodi', $prodi)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($jadwalAmi && !empty($jadwalAmi->tim_auditor)) {
                    $auditorAnggota = $jadwalAmi->tim_auditor;
                }
            }

            // Parse prodi string untuk mendapatkan kode dan nama prodi
            $prodiParts = explode(' - ', $prodi);
            $kodeProdi = trim($prodiParts[0]);
            $namaProdi = count($prodiParts) > 1 ? trim($prodiParts[1]) : $prodi;

            if (strpos($namaProdi, '(') !== false) {
                $namaProdiParts = explode('(', $namaProdi);
                $namaProdi = trim($namaProdiParts[0]);
            }

            // Cari informasi Kaprodi
            $kaprodi = "Tidak tersedia";
            try {
                if (class_exists('\App\Models\Siakad')) {
                    $kaprodiInfo = \App\Models\Siakad::getKaprodiByKodeUnit($kodeProdi);
                    if ($kaprodiInfo) {
                        $kaprodi = $kaprodiInfo;
                    }
                }
            } catch (\Exception $e) {
                // Jika gagal mendapatkan kaprodi, biarkan nilai default
            }

            // Ambil fakultas dari prodi
            $fakultas = "Tidak tersedia";
            if (isset($dokumen->fakultas)) {
                $fakultas = $dokumen->fakultas;
                if (strpos($fakultas, ' - ') !== false) {
                    $fakultasParts = explode(' - ', $fakultas);
                    if (count($fakultasParts) > 1) {
                        $fakultas = trim($fakultasParts[1]);
                    }
                }
            }

            // Tanggal audit
            $tanggalAudit = date('d F Y');
            $jadwalAmi = JadwalAmi::where('prodi', $prodi)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($jadwalAmi && $jadwalAmi->tanggal_mulai) {
                $tanggalAudit = Carbon::parse($jadwalAmi->tanggal_mulai)->format('d F Y');
            }

            $rawStatusTemuan = isset($dokumen->status_temuan) ? $dokumen->status_temuan : '';
            $upperStatusTemuan = strtoupper($rawStatusTemuan);

            // Kategori temuan untuk checkbox (semua kosong untuk data dummy)
            $kategoriTemuan = [
                'KTS' => false,
                'OB' => false,
                'TERCAPAI' => false,
            ];

            // Jika ada status temuan, set checkbox yang sesuai
            if (!empty($rawStatusTemuan)) {
                $kategoriTemuan = [
                    'KTS' => in_array($upperStatusTemuan, ['KETIDAKSESUAIAN', 'KETIDAKSESUAIAN (KTS)', 'KTS']),
                    'OB' => in_array($upperStatusTemuan, ['OBSERVASI', 'OBSERVASI (OB)', 'OB']),
                    'TERCAPAI' => in_array($upperStatusTemuan, ['TERCAPAI']),
                ];
            }

            // Siapkan data untuk PDF
            $viewData = [
                'dokumen' => $dokumen,
                'kriteriaDokumen' => $kriteriaDokumen,
                'lembagaAkreditasi' => $lembagaAkreditasi,
                'jenjang' => $jenjang,
                'prodi' => $namaProdi,
                'kodeProdi' => $kodeProdi,
                'kaprodi' => $kaprodi,
                'fakultas' => $fakultas,
                'auditorKetua' => $auditorKetua,
                'auditorAnggota' => $auditorAnggota,
                'tanggalAudit' => $tanggalAudit,
                'kategoriTemuan' => $kategoriTemuan,
                'kode' => $kode,
                'tanggal' => $tanggal,
                'revisi' => $revisi,
            ];

            // Generate PDF
            $pdf = \PDF::loadView('ptkrhr.ptk-form', $viewData);
            $pdf->setPaper('a4', 'portrait');

            // Nama file PDF
            $filename = 'PTK_' . $kodeProdi . '_' . $dokumen->kode . '_' . date('Ymd') . '.pdf';

            // Stream PDF di browser untuk preview
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }

    public function generateExcel($lembagaId, $jenjangId, $dokumenId, $prodi, Request $request)
    {
        try {
            // Get additional parameters from request
            $kode = $request->get('kode', '');
            $tanggal = $request->get('tanggal', '');
            $revisi = $request->get('revisi', '');

            // PERBAIKAN: Handle dokumenId 0 atau tidak ditemukan - sama seperti generatePdf
            $dokumen = null;
            $kriteriaDokumen = null;
            
            // Coba cari dokumen normal dulu
            if ($dokumenId > 0) {
                $dokumen = PenilaianKriteria::find($dokumenId);
            }
            
            // Jika tidak ditemukan atau dokumenId = 0, buat data kosong
            if (!$dokumen) {
                // Cari kriteria dokumen untuk data "INI BARU" berdasarkan prodi dan lembaga/jenjang
                $kriteriaDokumenItems = KriteriaDokumen::where([
                    'lembaga_akreditasi_id' => $lembagaId,
                    'jenjang_id' => $jenjangId
                ])->with('judulKriteriaDokumen')->get();
                
                // Ambil kriteria dokumen pertama yang tidak memiliki penilaian untuk prodi ini
                $kriteriaDokumen = null;
                foreach ($kriteriaDokumenItems as $kd) {
                    $existingPenilaian = PenilaianKriteria::where('kriteria_dokumen_id', $kd->id)
                        ->where('prodi', $prodi)
                        ->first();
                        
                    if (!$existingPenilaian) {
                        $kriteriaDokumen = $kd;
                        break;
                    }
                }
                
                // Jika tidak ada yang cocok, ambil yang pertama saja
                if (!$kriteriaDokumen && $kriteriaDokumenItems->isNotEmpty()) {
                    $kriteriaDokumen = $kriteriaDokumenItems->first();
                }
                
                if (!$kriteriaDokumen) {
                    return redirect()->back()->with('error', 'Tidak ada kriteria dokumen yang ditemukan');
                }

                // Buat objek dummy dengan data kosong
                $dokumen = new \stdClass();
                $dokumen->kode = $kriteriaDokumen->kode ?? 'INI BARU';
                $dokumen->hasil_ami = '-';
                $dokumen->akar_penyebab_masalah = '-';
                $dokumen->output = '-';
                $dokumen->tanggal_pemenuhan = null;
                $dokumen->tinjauan_efektivitas_koreksi = '-';
                $dokumen->kesimpulan = '-';
                $dokumen->status_temuan = '';
                $dokumen->fakultas = getActiveFakultas() ?? 'Tidak tersedia';
            } else {
                // Data normal yang ditemukan
                $kriteriaDokumen = KriteriaDokumen::find($dokumen->kriteria_dokumen_id);
                if (!$kriteriaDokumen) {
                    return redirect()->back()->with('error', 'Kriteria dokumen tidak ditemukan');
                }
            }

            // Cari data prodi dan auditor dari service
            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $prodi,
                Auth::user()
            );

            // Ambil tim_auditor dari prodiList dengan pemisahan ketua dan anggota
            $auditorKetua = '';
            $auditorAnggota = '';
            if (isset($data['prodiList'])) {
                foreach ($data['prodiList'] as $prodiItem) {
                    if ($prodiItem['prodi'] === $prodi) {
                        if (isset($prodiItem['tim_auditor_detail'])) {
                            $auditorKetua = $prodiItem['tim_auditor_detail']['ketua'] ?? '';
                            $auditorAnggota = isset($prodiItem['tim_auditor_detail']['anggota'])
                                ? implode(', ', $prodiItem['tim_auditor_detail']['anggota'])
                                : '';
                        } else {
                            $auditorAnggota = $prodiItem['tim_auditor'] ?? '';
                        }
                        break;
                    }
                }
            }

            // Jika masih kosong, coba cari dari JadwalAmi
            if (empty($auditorKetua) && empty($auditorAnggota)) {
                $jadwalAmi = JadwalAmi::where('prodi', $prodi)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($jadwalAmi && !empty($jadwalAmi->tim_auditor)) {
                    $auditorAnggota = $jadwalAmi->tim_auditor;
                }
            }

            // Parse prodi string untuk mendapatkan kode dan nama prodi
            $prodiParts = explode(' - ', $prodi);
            $kodeProdi = trim($prodiParts[0]);
            $namaProdi = count($prodiParts) > 1 ? trim($prodiParts[1]) : $prodi;

            if (strpos($namaProdi, '(') !== false) {
                $namaProdiParts = explode('(', $namaProdi);
                $namaProdi = trim($namaProdiParts[0]);
            }

            // Cari informasi Kaprodi
            $kaprodi = "Tidak tersedia";
            try {
                if (class_exists('\App\Models\Siakad')) {
                    $kaprodiInfo = \App\Models\Siakad::getKaprodiByKodeUnit($kodeProdi);
                    if ($kaprodiInfo) {
                        $kaprodi = $kaprodiInfo;
                    }
                }
            } catch (\Exception $e) {
                // Jika gagal mendapatkan kaprodi, biarkan nilai default
            }

            // Ambil fakultas dari prodi
            $fakultas = "Tidak tersedia";
            if (isset($dokumen->fakultas)) {
                $fakultas = $dokumen->fakultas;
                if (strpos($fakultas, ' - ') !== false) {
                    $fakultasParts = explode(' - ', $fakultas);
                    if (count($fakultasParts) > 1) {
                        $fakultas = trim($fakultasParts[1]);
                    }
                }
            }

            // Tanggal audit dengan format yang konsisten
            $tanggalAudit = date('j F Y');
            $jadwalAmi = JadwalAmi::where('prodi', $prodi)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($jadwalAmi && $jadwalAmi->tanggal_mulai) {
                $tanggalAudit = Carbon::parse($jadwalAmi->tanggal_mulai)->format('j F Y');
            }

            $rawStatusTemuan = isset($dokumen->status_temuan) ? $dokumen->status_temuan : '';
            $upperStatusTemuan = strtoupper($rawStatusTemuan);

            // Kategori temuan untuk checkbox (semua kosong untuk data dummy)
            $kategoriTemuan = [
                'KTS' => false,
                'OB' => false,
                'TERCAPAI' => false,
            ];

            // Jika ada status temuan, set checkbox yang sesuai
            if (!empty($rawStatusTemuan)) {
                $kategoriTemuan = [
                    'KTS' => in_array($upperStatusTemuan, ['KETIDAKSESUAIAN', 'KETIDAKSESUAIAN (KTS)', 'KTS']),
                    'OB' => in_array($upperStatusTemuan, ['OBSERVASI', 'OBSERVASI (OB)', 'OB']),
                    'TERCAPAI' => in_array($upperStatusTemuan, ['TERCAPAI']),
                ];
            }

            // Format tanggal pemenuhan dengan bahasa Indonesia
            $tanggalPemenuhan = '';
            if (isset($dokumen->tanggal_pemenuhan) && $dokumen->tanggal_pemenuhan) {
                $tanggalPemenuhan = Carbon::parse($dokumen->tanggal_pemenuhan)->locale('id')->format('j F Y');
            }

            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('PTK Form');

            // Set orientasi landscape dan ukuran kertas A4
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

            // Atur margin
            $sheet->getPageMargins()->setTop(0.5);
            $sheet->getPageMargins()->setBottom(0.5);
            $sheet->getPageMargins()->setLeft(0.5);
            $sheet->getPageMargins()->setRight(0.5);

            // Atur lebar kolom yang optimal sebelum mengisi data
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(20);

            $currentRow = 1;

            // HEADER SECTION - ROW 1-2
            $sheet->setCellValue('A1', '');
            $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Informasi Universitas
            $sheet->mergeCells('B1:H1');
            $sheet->setCellValue('B1', 'UNIVERSITAS PGRI ADI BUANA Surabaya');
            $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B2:H2');
            $sheet->setCellValue('B2', 'Jl. Dukuh Menanggal XII, Surabaya, 60234 Telp. (031) 8289637, Fax. (031) 8289637');
            $sheet->getStyle('B2')->getFont()->setSize(10);
            $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Kode
            $sheet->setCellValue('I1', 'KODE');
            $sheet->getStyle('I1')->getFont()->setBold(true);
            $sheet->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('I2', $kode);
            $sheet->getStyle('I2')->getFont()->setBold(true);
            $sheet->getStyle('I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $currentRow = 3;

            // ROW 3 - Buku, Form Title, Tanggal/Revisi
            $sheet->setCellValue('A3', 'BUKU 5');
            $sheet->getStyle('A3')->getFont()->setBold(true);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B3:H3');
            $sheet->setCellValue('B3', 'FORMULIR PERMINTAAN TINDAKAN KOREKSI');
            $sheet->getStyle('B3')->getFont()->setBold(true);
            $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('I3', 'Tanggal: ' . $tanggal);

            $currentRow = 4;

            // Revisi
            $sheet->setCellValue('I4', 'Revisi: ' . $revisi);

            $currentRow = 5;

            // ROW 5 - Judul Utama PTK
            $sheet->mergeCells('A5:I5');
            $sheet->setCellValue('A5', 'PERMINTAAN TINDAKAN KOREKSI (PTK)');
            $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $currentRow = 6;

            // FORM CONTENT
            // Program Studi
            $sheet->setCellValue('A' . $currentRow, 'Program Studi/unit kerja');
            $sheet->mergeCells('B' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('B' . $currentRow, $namaProdi);
            $currentRow++;

            // Fakultas
            $sheet->setCellValue('A' . $currentRow, 'Fakultas /Unit Kerja');
            $sheet->mergeCells('B' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('B' . $currentRow, $fakultas);
            $currentRow++;

            // Kaprodi
            $sheet->setCellValue('A' . $currentRow, 'Kaprodi /Ka unit kerja');
            $sheet->mergeCells('B' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('B' . $currentRow, $kaprodi);
            $currentRow++;

            // Nama Auditor dengan sub-table
            $sheet->setCellValue('A' . $currentRow, 'Nama Auditor');

            // Sub-table untuk Ketua/Anggota
            $sheet->setCellValue('B' . $currentRow, 'Ketua');
            $sheet->mergeCells('C' . $currentRow . ':F' . $currentRow);
            $sheet->setCellValue('C' . $currentRow, $auditorKetua);

            $sheet->mergeCells('G' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('G' . $currentRow, 'Tanggal Audit: ' . $tanggalAudit);
            $currentRow++;

            // Anggota auditor
            $sheet->setCellValue('B' . $currentRow, 'Anggota');
            $sheet->mergeCells('C' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('C' . $currentRow, $auditorAnggota);
            $currentRow++;

            // PTK No dan Kategori
            $sheet->setCellValue('A' . $currentRow, 'PTK No ' . $dokumen->kode);
            $sheet->mergeCells('B' . $currentRow . ':I' . $currentRow);

            $statusText = 'Kategori: ';
            $statusText .= $kategoriTemuan['KTS'] ? '[✓] KTS ' : '[ ] KTS ';
            $statusText .= $kategoriTemuan['OB'] ? '[✓] Observasi (OB) ' : '[ ] Observasi (OB) ';
            $statusText .= $kategoriTemuan['TERCAPAI'] ? '[✓] TERCAPAI' : '[ ] TERCAPAI';
            $sheet->setCellValue('B' . $currentRow, $statusText);
            $currentRow++;

            // Referensi
            $sheet->setCellValue('A' . $currentRow, 'Referensi (butir mutu) (indicator)');
            $sheet->mergeCells('B' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('B' . $currentRow, $kriteriaDokumen->element . ' (' . $kriteriaDokumen->indikator . ')');
            $currentRow++;

            // DESKRIPSI TEMUAN
            $sheet->mergeCells('A' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('A' . $currentRow, 'Deskripsi temuan (KTS) (Hasil AMI): (diisi oleh auditor)');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Area input untuk deskripsi (3 rows)
            $sheet->mergeCells('A' . $currentRow . ':I' . ($currentRow + 2));
            $sheet->setCellValue('A' . $currentRow, isset($dokumen->hasil_ami) ? $dokumen->hasil_ami : '-');
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            for ($i = 0; $i < 3; $i++) {
                $sheet->getRowDimension($currentRow + $i)->setRowHeight(25);
            }
            $currentRow += 3;

            // Tanda tangan auditor
            $sheet->setCellValue('A' . $currentRow, 'Nama Auditor:');
            $sheet->setCellValue('A' . ($currentRow + 1), 'Ketua: ' . $auditorKetua);
            $sheet->setCellValue('A' . ($currentRow + 2), 'Anggota: ' . $auditorAnggota);

            $sheet->setCellValue('D' . $currentRow, 'Tanda tangan');
            $sheet->mergeCells('G' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('G' . $currentRow, 'Tanggal audit: ' . $tanggalAudit);
            $currentRow += 3;

            // AKAR MASALAH
            $sheet->mergeCells('A' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('A' . $currentRow, 'AKAR MASALAH: (diisi auditee)');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Area input untuk akar masalah
            $sheet->mergeCells('A' . $currentRow . ':I' . ($currentRow + 2));
            $sheet->setCellValue('A' . $currentRow, isset($dokumen->akar_penyebab_masalah) ? $dokumen->akar_penyebab_masalah : '-');
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            for ($i = 0; $i < 3; $i++) {
                $sheet->getRowDimension($currentRow + $i)->setRowHeight(25);
            }
            $currentRow += 3;

            // Tanda tangan auditee untuk akar masalah
            $sheet->setCellValue('A' . $currentRow, 'Auditee:');
            $sheet->setCellValue('A' . ($currentRow + 1), $kaprodi);
            $sheet->setCellValue('D' . $currentRow, 'Tanda tangan');
            $sheet->mergeCells('G' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('G' . $currentRow, 'Tanggal audit: ' . $tanggalAudit);
            $currentRow += 2;

            // RENCANA TINDAKAN KOREKSI
            $sheet->mergeCells('A' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('A' . $currentRow, 'RENCANA TINDAKAN KOREKSI (rekomendasi): (diisi auditee setelah konsultasi dengan pimpinan dan mendapatkan persetujuan auditor)');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Area input untuk rencana
            $sheet->mergeCells('A' . $currentRow . ':I' . ($currentRow + 2));
            $sheet->setCellValue('A' . $currentRow, isset($dokumen->output) ? $dokumen->output : '-');
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            for ($i = 0; $i < 3; $i++) {
                $sheet->getRowDimension($currentRow + $i)->setRowHeight(25);
            }
            $currentRow += 3;

            // Tanda tangan auditee untuk rencana
            $sheet->setCellValue('A' . $currentRow, 'Auditee:');
            $sheet->setCellValue('A' . ($currentRow + 1), $kaprodi);
            $sheet->setCellValue('D' . $currentRow, 'Tanda tangan');
            $sheet->mergeCells('G' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('G' . $currentRow, 'Tanggal: ' . $tanggalPemenuhan);
            $currentRow += 2;

            // TINJAUAN EFEKTIVITAS KOREKSI
            $sheet->mergeCells('A' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('A' . $currentRow, 'TINJAUAN EFEKTIFITAS KOREKSI: (diisi oleh auditor pada audit berikutnya/audit tindak lanjut)');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Area input untuk tinjauan
            $sheet->mergeCells('A' . $currentRow . ':I' . ($currentRow + 2));
            $sheet->setCellValue('A' . $currentRow, isset($dokumen->tinjauan_efektivitas_koreksi) ? $dokumen->tinjauan_efektivitas_koreksi : '-');
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            for ($i = 0; $i < 3; $i++) {
                $sheet->getRowDimension($currentRow + $i)->setRowHeight(25);
            }
            $currentRow += 3;

            // Kesimpulan
            $sheet->mergeCells('A' . $currentRow . ':I' . ($currentRow + 1));
            $sheet->setCellValue('A' . $currentRow, 'Kesimpulan: ' . (isset($dokumen->kesimpulan) ? $dokumen->kesimpulan : '(status dinyatakan selesai/terbitan PTK baru) coret salah satu'));
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
            $currentRow += 2;

            // Auditor tindak lanjut
            $sheet->setCellValue('A' . $currentRow, 'Auditor Tindak Lanjut:');
            $sheet->setCellValue('A' . ($currentRow + 1), 'Ketua: ' . $auditorKetua);
            $sheet->setCellValue('A' . ($currentRow + 2), 'Anggota: ' . $auditorAnggota);

            $sheet->setCellValue('D' . $currentRow, 'Tanda tangan');
            $sheet->mergeCells('G' . $currentRow . ':I' . $currentRow);
            $sheet->setCellValue('G' . $currentRow, 'Tanggal: ' . $tanggalPemenuhan);
            $currentRow += 2;

            // LOGO HANDLING
            $logoAdded = false;
            $possibleLogoPaths = [
                public_path('img/picture_logo.png'),
                public_path('images/picture_logo.png'),
                public_path('assets/img/picture_logo.png'),
                public_path('storage/img/picture_logo.png'),
            ];

            foreach ($possibleLogoPaths as $logoPath) {
                if (file_exists($logoPath) && is_readable($logoPath)) {
                    try {
                        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $drawing->setName('Logo Universitas');
                        $drawing->setDescription('Logo PGRI Adi Buana');
                        $drawing->setPath($logoPath);
                        $drawing->setCoordinates('A1');
                        $drawing->setHeight(50);
                        $drawing->setWidth(50);
                        $drawing->setOffsetX(15);
                        $drawing->setOffsetY(10);
                        $drawing->setWorksheet($sheet);
                        $logoAdded = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            // Jika logo tidak bisa ditambahkan, ganti dengan text
            if (!$logoAdded) {
                $sheet->setCellValue('A1', 'LOGO');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            // STYLING DAN BORDERS
            $totalRows = $currentRow;

            // Border untuk seluruh tabel
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:I' . $totalRows)->applyFromArray($styleArray);

            // Font untuk seluruh dokumen
            $sheet->getStyle('A1:I' . $totalRows)->getFont()->setSize(10);

            // Set tinggi minimum untuk header rows
            $sheet->getRowDimension(1)->setRowHeight(30);
            $sheet->getRowDimension(2)->setRowHeight(25);
            $sheet->getRowDimension(3)->setRowHeight(25);
            $sheet->getRowDimension(5)->setRowHeight(30);

            // Buat writer
            $writer = new Xlsx($spreadsheet);

            // Nama file
            $filename = 'PTK_' . $kodeProdi . '_' . $dokumen->kode . '_' . date('Ymd') . '.xlsx';

            // Header untuk download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');

            // Output langsung ke PHP output
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat Excel: ' . $e->getMessage());
        }
    }

    public function generateRekapitulasiPdf($lembagaId, $jenjangId, $dokumenId, $prodi, Request $request)
    {
        try {
            // Get additional parameters from request (kode, tanggal, revisi)
            $kode = $request->get('kode', '');
            $tanggal = $request->get('tanggal', '');
            $revisi = $request->get('revisi', '');

            // Ambil data dokumen
            $dokumen = PenilaianKriteria::find($dokumenId);
            if (!$dokumen) {
                return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
            }

            // Ambil data kriteria dokumen
            $kriteriaDokumen = KriteriaDokumen::find($dokumen->kriteria_dokumen_id);
            if (!$kriteriaDokumen) {
                return redirect()->back()->with('error', 'Kriteria dokumen tidak ditemukan');
            }

            // Ambil data lembaga akreditasi dan jenjang
            $lembagaAkreditasi = $kriteriaDokumen->lembagaAkreditasi;
            $jenjang = $kriteriaDokumen->jenjang;

            // Cari data prodi dan auditor dari service
            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $prodi,
                Auth::user()
            );

            // Ambil tim_auditor dari prodiList dengan pemisahan ketua dan anggota
            $auditorKetua = '';
            $auditorAnggota = '';
            if (isset($data['prodiList'])) {
                foreach ($data['prodiList'] as $prodiItem) {
                    if ($prodiItem['prodi'] === $prodi) {
                        if (isset($prodiItem['tim_auditor_detail'])) {
                            $auditorKetua = $prodiItem['tim_auditor_detail']['ketua'] ?? '';
                            $auditorAnggota = isset($prodiItem['tim_auditor_detail']['anggota'])
                                ? implode(', ', $prodiItem['tim_auditor_detail']['anggota'])
                                : '';
                        } else {
                            // Fallback ke tim_auditor string jika detail tidak ada
                            $auditorAnggota = $prodiItem['tim_auditor'] ?? '';
                        }
                        break;
                    }
                }
            }

            // Jika masih kosong, coba cari dari JadwalAmi
            if (empty($auditorKetua) && empty($auditorAnggota)) {
                $jadwalAmi = JadwalAmi::where('prodi', $prodi)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($jadwalAmi && !empty($jadwalAmi->tim_auditor)) {
                    $auditorAnggota = $jadwalAmi->tim_auditor;
                }
            }

            // Parse prodi string untuk mendapatkan kode dan nama prodi
            $prodiParts = explode(' - ', $prodi);
            $kodeProdi = trim($prodiParts[0]);
            $namaProdi = count($prodiParts) > 1 ? trim($prodiParts[1]) : $prodi;

            // Jika nama prodi masih mengandung jenjang dalam kurung (misalnya "Farmasi (S1)")
            if (strpos($namaProdi, '(') !== false) {
                $namaProdiParts = explode('(', $namaProdi);
                $namaProdi = trim($namaProdiParts[0]);
            }

            // Cari informasi Kaprodi
            $kaprodi = "Tidak tersedia";
            try {
                if (class_exists('\App\Models\Siakad')) {
                    $kaprodiInfo = \App\Models\Siakad::getKaprodiByKodeUnit($kodeProdi);
                    if ($kaprodiInfo) {
                        $kaprodi = $kaprodiInfo;
                    }
                }
            } catch (\Exception $e) {
                // Jika gagal mendapatkan kaprodi, biarkan nilai default
            }
            // Ambil fakultas dari prodi dan hapus kode jika ada
            $fakultas = "Tidak tersedia";
            if (isset($dokumen->fakultas)) {
                $fakultas = $dokumen->fakultas;

                // Jika fakultas memiliki format "Kode - Nama Fakultas", ekstrak hanya nama fakultasnya
                if (strpos($fakultas, ' - ') !== false) {
                    $fakultasParts = explode(' - ', $fakultas);
                    if (count($fakultasParts) > 1) {
                        $fakultas = trim($fakultasParts[1]);
                    }
                }
            }

            // Tanggal audit
            $tanggalAudit = date('d F Y');
            $jadwalAmi = JadwalAmi::where('prodi', $prodi)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($jadwalAmi && $jadwalAmi->tanggal_mulai) {
                $tanggalAudit = Carbon::parse($jadwalAmi->tanggal_mulai)->format('d F Y');
            }

            // ============================================================================
            // BAGIAN YANG DIPERBAIKI: PENANGGUNG JAWAB MENGGUNAKAN LOGIC 2 KEMUNGKINAN
            // ============================================================================

            // Gunakan logic yang sama seperti di prepareDetailData()
            $penanggungJawab = $this->getPenanggungJawab($dokumen, $prodi);

            // ============================================================================
            // PENAMBAHAN BARU: LOGIC BUKTI FISIK BERDASARKAN KELENGKAPAN DOKUMEN
            // ============================================================================

            // Hitung total kebutuhan dan capaian dokumen untuk menentukan status bukti fisik
            $totalKebutuhan = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $dokumen->kriteria_dokumen_id)->count();
            $capaianDokumen = PemenuhanDokumen::where('kriteria_dokumen_id', $dokumen->kriteria_dokumen_id)
                ->where('prodi', $prodi)
                ->count();

            // Tentukan status bukti fisik
            $buktiFisikStatus = '';
            if ($totalKebutuhan == 0) {
                $buktiFisikStatus = 'Tidak Ada Persyaratan';
            } elseif ($capaianDokumen == 0) {
                $buktiFisikStatus = 'Tidak Ada';
            } elseif ($capaianDokumen < $totalKebutuhan) {
                $buktiFisikStatus = 'Belum Lengkap';
            } else {
                $buktiFisikStatus = 'Ada';
            }

            // Siapkan data temuan untuk tabel rekapitulasi
            $temuanItems = [];

            // Simpan data temuan dari dokumen ini
            $temuanItem = [
                'nomor' => 1,
                'uraian_temuan' => $dokumen->hasil_ami,
                'akar_penyebab' => $dokumen->akar_penyebab_masalah,
                'rencana_tindak' => $dokumen->revisi,
                'output' => $dokumen->output,
                'tanggal_pemenuhan' => $dokumen->tanggal_pemenuhan,
                'penanggung_jawab' => $penanggungJawab, // ← MENGGUNAKAN LOGIC BARU
                'bukti_fisik' => $buktiFisikStatus, // ← MENGGUNAKAN LOGIC BUKTI FISIK BARU
                'hasil_verifikasi' => $dokumen->hasil_verifikasi
            ];

            $temuanItems[] = $temuanItem;

            // Siapkan data untuk PDF
            $data = [
                'dokumen' => $dokumen,
                'kriteriaDokumen' => $kriteriaDokumen,
                'lembagaAkreditasi' => $lembagaAkreditasi,
                'jenjang' => $jenjang,
                'prodi' => $namaProdi,
                'kodeProdi' => $kodeProdi,
                'kaprodi' => $kaprodi,
                'fakultas' => $fakultas,
                'auditorKetua' => $auditorKetua,
                'auditorAnggota' => $auditorAnggota,
                'tanggalAudit' => $tanggalAudit,
                'kode' => $kode,
                'tanggal' => $tanggal,
                'revisi' => $revisi,
                'temuanItems' => $temuanItems
            ];

            // Generate PDF
            $pdf = \PDF::loadView('ptkrhr.rekapitulasi-form', $data);
            $pdf->setPaper('a4', 'landscape'); // Set landscape untuk tabel yang lebar

            // Nama file PDF
            $filename = 'Rekapitulasi_' . $kodeProdi . '_' . $dokumen->kode . '_' . date('Ymd') . '.pdf';

            // Stream PDF di browser untuk preview
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }
}
