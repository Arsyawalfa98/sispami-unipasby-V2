<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PemenuhanDokumen;
use App\Models\Siakad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PenilaianKriteria;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $widgets = [];
        $charts = [];
        $jadwalAuditor = null; // Tambahkan variabel untuk menyimpan jadwal auditor
        
        if (Auth::user()->hasActiveRole('Auditor')) {
            $jadwalAuditor = \DB::table('jadwal_ami')
                ->join('jadwal_ami_auditor', 'jadwal_ami.id', '=', 'jadwal_ami_auditor.jadwal_ami_id')
                ->where('jadwal_ami_auditor.user_id', $user->id)
                ->select('jadwal_ami.id', 'jadwal_ami.prodi', 'jadwal_ami.periode', 'jadwal_ami.tanggal_mulai', 'jadwal_ami.tanggal_selesai')
                ->get();
        }
        
        // Variable untuk menyimpan daftar prodi berdasarkan fakultas (untuk role Fakultas)
        $prodisByFakultas = null;
    
        // Jika user memiliki role Fakultas dan memiliki nilai fakultas
        if (Auth::user()->hasActiveRole('Fakultas') && $user->fakultas) {
            // Ambil daftar prodi yang terjadwal berdasarkan fakultas user
            $prodisByFakultas = \DB::table('jadwal_ami')
                ->select('id', 'prodi', 'periode', 'tanggal_mulai', 'tanggal_selesai')
                ->where('fakultas', $user->fakultas)  // Filter jadwal berdasarkan fakultas user
                ->distinct()
                ->get();
        }
    
        // Tambahkan selectedJadwal
        $selectedJadwalId = $request->input('jadwal_id');
    
        // Ambil daftar periode yang tersedia dari PenilaianKriteria (bukan PemenuhanDokumen)
        $periodes = PenilaianKriteria::select('periode_atau_tahun as periode')
            ->distinct()
            ->orderBy('periode_atau_tahun', 'desc')
            ->pluck('periode');
    
        // Ambil periode yang dipilih dari request, atau gunakan periode terbaru
        $selectedPeriode = $request->input('periode', $periodes->first());
    
        // Variabel untuk filter prodi
        $selectedProdi = null;
        $selectedProdiText = null;
    
        // Tentukan siapa yang bisa filter berdasarkan prodi
        // Tambahkan Fakultas agar bisa filter berdasarkan prodi dari fakultasnya
        $canFilterByProdi = Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM') || Auth::user()->hasActiveRole('Auditor') || Auth::user()->hasActiveRole('Fakultas');
    
        // Jika ada prodi yang dipilih
        if ($canFilterByProdi && $request->filled('prodi')) {
            $selectedProdi = $request->input('prodi');
    
            // Coba cari informasi prodi lengkap
            $prodiInfo = collect(Siakad::searchProdi($selectedProdi))->first();
            if ($prodiInfo) {
                $selectedProdiText = $prodiInfo->kodeunit . ' - ' . $prodiInfo->namaunit;
            } else {
                $selectedProdiText = $selectedProdi;
            }
        }
    
        // Widget jumlah user (hanya untuk super admin)
        if (Auth::user()->hasActiveRole('Super Admin')) {
            $widgets['users'] = User::count();
        }
    
        // Data penilaian berdasarkan role
        $baseQuery = PenilaianKriteria::query();
    
        // Filter by role dan prodi
        if (Auth::user()->hasActiveRole('Admin Prodi') && !Auth::user()->hasActiveRole('Admin LPM')) {
            // Admin biasa hanya bisa melihat data prodinya
            $baseQuery->where('prodi', $user->prodi);
        } elseif (Auth::user()->hasActiveRole('Auditor')) {
            // Auditor hanya bisa melihat data prodi yang ditugaskan padanya
            if ($selectedJadwalId) {
                // Jika ada jadwal yang dipilih, dapatkan prodinya
                $selectedJadwal = \DB::table('jadwal_ami')
                    ->join('jadwal_ami_auditor', 'jadwal_ami.id', '=', 'jadwal_ami_auditor.jadwal_ami_id')
                    ->where('jadwal_ami.id', $selectedJadwalId)
                    ->where('jadwal_ami_auditor.user_id', $user->id)
                    ->select('jadwal_ami.prodi')
                    ->first();
                    
                if ($selectedJadwal) {
                    $baseQuery->where('prodi', 'like', "%{$selectedJadwal->prodi}%");
                } else {
                    $baseQuery->whereRaw('1=0'); // Jadwal tidak ditemukan atau tidak ditugaskan ke auditor ini
                }
            } else {
                // Jika tidak ada jadwal yang dipilih, tampilkan data dari semua jadwal yang ditugaskan
                $assignedJadwalIds = \DB::table('jadwal_ami_auditor')
                    ->where('user_id', $user->id)
                    ->pluck('jadwal_ami_id')
                    ->toArray();
                    
                if (!empty($assignedJadwalIds)) {
                    $assignedProdiList = \DB::table('jadwal_ami')
                        ->whereIn('id', $assignedJadwalIds)
                        ->pluck('prodi')
                        ->toArray();
                    
                    if (!empty($assignedProdiList)) {
                        $baseQuery->where(function ($query) use ($assignedProdiList) {
                            foreach ($assignedProdiList as $assignedProdi) {
                                $query->orWhere('prodi', 'like', "%$assignedProdi%");
                            }
                        });
                    } else {
                        $baseQuery->whereRaw('1=0'); // Tidak ada prodi yang ditugaskan
                    }
                } else {
                    $baseQuery->whereRaw('1=0'); // Tidak ada jadwal yang ditugaskan
                }
            }
        } elseif (Auth::user()->hasActiveRole('Fakultas')) {
            // Jika role Fakultas dan ada jadwal yang dipilih dari dropdown
            if ($selectedJadwalId) {
                // Jika ada jadwal yang dipilih, dapatkan prodinya
                $selectedJadwal = \DB::table('jadwal_ami')
                    ->where('id', $selectedJadwalId)
                    ->where('fakultas', $user->fakultas)  // Pastikan jadwal ini milik fakultas user
                    ->select('prodi')
                    ->first();
                    
                if ($selectedJadwal) {
                    $baseQuery->where('prodi', 'like', "%{$selectedJadwal->prodi}%");
                } else {
                    $baseQuery->whereRaw('1=0'); // Jadwal tidak ditemukan atau bukan milik fakultas ini
                }
            } else {
                // Jika tidak ada prodi yang dipilih, tampilkan semua data fakultas tersebut
                $baseQuery->where('fakultas', $user->fakultas);
            }
        } elseif (!Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM') && $user->fakultas) {
            // Filter berdasarkan fakultas user (untuk role lain yang memiliki fakultas)
            $baseQuery->where('fakultas', $user->fakultas);
        } elseif ($canFilterByProdi && $selectedProdi) {
            // Jika Super Admin atau Admin LPM dan prodi dipilih (gunakan LIKE untuk lebih fleksibel)
            $baseQuery->where('prodi', 'like', "%$selectedProdiText%");
        }
    
        // Filter by periode
        if ($selectedPeriode && $selectedPeriode !== 'all') {
            $baseQuery->where('periode_atau_tahun', $selectedPeriode);
        }
    
        // Total penilaian
        $widgets['total_dokumen'] = (clone $baseQuery)->count();
    
        // Penilaian berdasarkan status
        $statusCounts = (clone $baseQuery)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
    
        $widgets['dokumen_diajukan'] = $statusCounts[PenilaianKriteria::STATUS_DIAJUKAN] ?? 0;
        $widgets['dokumen_disetujui'] = $statusCounts[PenilaianKriteria::STATUS_DISETUJUI] ?? 0;
    
        // Dokumen dengan revisi (yang mempunyai nilai revisi tidak null)
        $widgets['dokumen_revisi'] = (clone $baseQuery)->whereNotNull('revisi')
            ->where('revisi', '!=', '')
            ->count();
    
        // Rata-rata nilai
        $widgets['rata_nilai'] = (clone $baseQuery)->whereNotNull('nilai')->avg('nilai') ?: 0;
    
        // "Detail penilaian per kriteria"
        $dokumenDetail = [];
    
        if ($selectedProdi || (!$canFilterByProdi)) {
            // Ambil semua penilaian dengan kriteria dokumen terkait
            $penilaianList = (clone $baseQuery)
                ->with('kriteriaDokumen.judulKriteriaDokumen')
                ->get();
                
            // Kelompokkan berdasarkan kriteria
            foreach ($penilaianList as $penilaian) {
                $kriteriaName = $penilaian->kriteriaDokumen->judulKriteriaDokumen->nama_kriteria_dokumen ?? 'Tidak Diketahui';
                
                if (!isset($dokumenDetail[$kriteriaName])) {
                    $dokumenDetail[$kriteriaName] = collect();
                }
                
                // Ambil data nama_dokumen dan tambahan_informasi dari tabel pemenuhan_dokumen
                $pemenuhanDokumen = PemenuhanDokumen::where('kriteria_dokumen_id', $penilaian->kriteria_dokumen_id)
                    ->where('prodi', $penilaian->prodi)
                    ->first();
                
                // Tambahkan ke grup kriteria
                $dokumenDetail[$kriteriaName]->push([
                    'id' => $penilaian->id,
                    'kode' => $penilaian->kriteriaDokumen->kode ?? '-',
                    'nama_dokumen' => $pemenuhanDokumen ? $pemenuhanDokumen->nama_dokumen : '',
                    'element' => $penilaian->kriteriaDokumen->element ?? '-',
                    'indikator' => $penilaian->kriteriaDokumen->indikator ?? '-',
                    'nilai' => $penilaian->nilai,
                    'sebutan' => $penilaian->sebutan,
                    'bobot' => $penilaian->bobot,
                    'tertimbang' => $penilaian->tertimbang,
                    'nilai_auditor' => $penilaian->nilai_auditor,
                    'revisi' => $penilaian->revisi,
                    'periode' => $penilaian->periode_atau_tahun,
                    'informasi'=> $penilaian->kriteriaDokumen->informasi,
                    'tambahan_informasi' => $pemenuhanDokumen ? $pemenuhanDokumen->tambahan_informasi : ''
                ]);
            }
        }
    
        // Data untuk chart status
        $chartStatusData = [];
        if ($selectedProdi || (!$canFilterByProdi)) {
            // Jika ada filter prodi atau bukan Super Admin/Admin LPM, tampilkan chart biasa
            $statusCounts = (clone $baseQuery)
                ->select('status', DB::raw('count(*) as total'))
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->groupBy('status')
                ->get();
    
            foreach ($statusCounts as $item) {
                $chartStatusData[] = [
                    'label' => ucfirst($item->status),
                    'value' => $item->total
                ];
            }
        } else {
            // Jika tidak ada filter prodi dan user adalah Super Admin/Admin LPM, 
            // tampilkan status berdasarkan prodi
            $statusCounts = (clone $baseQuery)
                ->select('prodi', 'status', DB::raw('count(*) as total'))
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->groupBy('prodi', 'status')
                ->get();
    
            foreach ($statusCounts as $item) {
                // Ekstrak kode prodi untuk label ringkas
                $prodiParts = explode(' - ', $item->prodi);
                $prodiKode = $prodiParts[0];
    
                $chartStatusData[] = [
                    'label' => $item->prodi . ': ' . ucfirst($item->status),
                    'value' => $item->total,
                    'fullProdi' => $item->prodi // Simpan nama prodi lengkap
                ];
            }
        }
        $charts['status'] = json_encode($chartStatusData);
    
        // Data untuk chart nilai berdasarkan kategori
        $chartNilai = [];
        if ($selectedProdi || (!$canFilterByProdi)) {
            // Jika ada filter prodi atau bukan Super Admin/Admin LPM, tampilkan chart biasa
            $sebutanCounts = (clone $baseQuery)
                ->select('sebutan', DB::raw('count(*) as total'))
                ->whereNotNull('sebutan')
                ->where('sebutan', '!=', '')
                ->groupBy('sebutan')
                ->get();
    
            foreach ($sebutanCounts as $item) {
                $chartNilai[] = [
                    'label' => $item->sebutan ?: 'Tidak Ada',
                    'value' => $item->total
                ];
            }
        } else {
            // Jika tidak ada filter prodi dan user adalah Super Admin/Admin LPM,
            // tampilkan nilai berdasarkan prodi
            $sebutanCounts = (clone $baseQuery)
                ->select('prodi', 'sebutan', DB::raw('count(*) as total'))
                ->whereNotNull('sebutan')
                ->where('sebutan', '!=', '')
                ->groupBy('prodi', 'sebutan')
                ->get();
    
            foreach ($sebutanCounts as $item) {
                // Ekstrak kode prodi untuk label ringkas
                $prodiParts = explode(' - ', $item->prodi);
                $prodiKode = $prodiParts[0];
    
                $chartNilai[] = [
                    'label' => $item->prodi . ': ' . ($item->sebutan ?: 'Tidak Ada'),
                    'value' => $item->total,
                    'fullProdi' => $item->prodi // Simpan nama prodi lengkap
                ];
            }
        }
        $charts['nilai'] = json_encode($chartNilai);
    
        return view('home', compact(
            'widgets',
            'charts',
            'periodes',
            'selectedPeriode',
            'selectedProdi',
            'selectedProdiText',
            'canFilterByProdi',
            'dokumenDetail',
            'jadwalAuditor',
            'selectedJadwalId',
            'prodisByFakultas'  // Tambahkan variabel ini
        ));
    }

    /**
     * Search prodi for autocomplete.
     */
    public function searchProdi(Request $request)
    {
        $search = $request->search;
        $prodis = Siakad::searchProdi($search);

        $formattedProdi = collect($prodis)->map(function ($prodi) {
            return [
                'id' => $prodi->kodeunit,
                'text' => $prodi->kodeunit . ' - ' . $prodi->namaunit,
                'fakultas' => [
                    'id' => $prodi->fakultas_kode,
                    'text' => $prodi->fakultas_kode . ' - ' . $prodi->fakultas_nama
                ]
            ];
        });

        return response()->json($formattedProdi);
    }
}