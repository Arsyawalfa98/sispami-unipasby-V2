<?php

namespace App\Http\Controllers;

use App\Models\KriteriaDokumen;
use App\Models\PemenuhanDokumen;
use App\Models\PenilaianKriteria; // Tambahkan model baru
use App\Models\KelolaKebutuhanKriteriaDokumen;
use App\Models\JadwalAmi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;
use App\Services\ActivityLogService;

class DokumenPersyaratanPemenuhanDokumenController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    protected function isJadwalActive($prodi, $periode)
    {
        $jadwalAmi = JadwalAmi::where('prodi', 'like', "%{$prodi}%")
            ->whereRaw("LEFT(periode, 4) = ?", [$periode])
            ->first();

        if (!$jadwalAmi) {
            return false;
        }

        $jadwalMulai = \Carbon\Carbon::parse($jadwalAmi->tanggal_mulai);
        $jadwalSelesai = \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai);
        $now = \Carbon\Carbon::now();

        return $now->between($jadwalMulai, $jadwalSelesai);
    }

    public function index($kriteriaDokumenId)
    {
        $user = Auth::user();
        $selectedProdi = request('prodi');

        // Get base kriteria dokumen data
        $kriteriaDokumen = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang', 'judulKriteriaDokumen'])
            ->findOrFail($kriteriaDokumenId);

        // Get kebutuhan dokumen
        $kebutuhanDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->get();

        // Buat mapping kebutuhan dokumen berdasarkan nama_dokumen
        $kebutuhanMap = $kebutuhanDokumen->keyBy('nama_dokumen');

        // Filter pemenuhan dokumen by prodi
        $pemenuhanDokumen = PemenuhanDokumen::with('tipeDokumen')
            ->where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->where('prodi', $selectedProdi) // Filter by selected prodi
            ->orderBy('created_at', 'desc')
            ->get();
        // Tambahkan data bobot dari kebutuhan ke pemenuhan dokumen
        foreach ($pemenuhanDokumen as $dokumen) {
            if ($kebutuhanMap->has($dokumen->nama_dokumen)) {
                $dokumen->kebutuhan_info = $kebutuhanMap[$dokumen->nama_dokumen];
            }
        }


        // Ambil data penilaian kriteria jika ada
        $penilaianKriteria = PenilaianKriteria::where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->where('prodi', $selectedProdi)
            ->first();
        return view('dokumen-persyaratan-pemenuhan-dokumen.index', [
            'kriteriaDokumen' => $kriteriaDokumen,
            'pemenuhanDokumen' => $pemenuhanDokumen,
            'kebutuhanDokumen' => $kebutuhanDokumen,
            'selectedProdi' => $selectedProdi, // Pass selected prodi to view
            'penilaianKriteria' => $penilaianKriteria // Pass penilaian data to view
        ]);
    }

    public function create($kriteriaDokumenId)
    {

        $selectedProdi = request('prodi');

        // Load kriteria dokumen dengan seluruh relasinya
        $kriteriaDokumen = KriteriaDokumen::with([
            'lembagaAkreditasi',
            'jenjang',
            'judulKriteriaDokumen'
        ])->findOrFail($kriteriaDokumenId);

        // Ambil data pemenuhan dokumen yang sudah ada
        $pemenuhanDokumen = PemenuhanDokumen::where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->where('prodi', $selectedProdi)
            ->get();

        // PERBAIKAN: Load tipe dokumen dengan relasi yang benar dan eager loading
        $kebutuhanDokumen = KelolaKebutuhanKriteriaDokumen::with('tipeDokumen')
            ->where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->get();

        // Pastikan tipe dokumen dimuat dengan benar untuk tiap kebutuhan dokumen
        foreach ($kebutuhanDokumen as $kebutuhan) {
            // Jika tipe_dokumen ada tapi tipeDokumen (relasi) tidak dimuat, muat manual
            if (!empty($kebutuhan->tipe_dokumen) && empty($kebutuhan->tipeDokumen)) {
                $kebutuhan->load('tipeDokumen');
            }
        }

        // Siapkan semua tipe dokumen untuk digunakan di JavaScript
        $allTipeDokumen = \App\Models\TipeDokumen::all();

        // Pastikan data bobot tersedia dari kriteria dokumen
        if (!isset($kriteriaDokumen->bobot)) {
            $kriteriaDokumen->bobot = 0;
        }
        //dd($kriteriaDokumen->periode_atau_tahun );
        //dd(explode(" - ",$selectedProdi)[0] );
        $namefile = 'file_' . $kriteriaDokumen->periode_atau_tahun;
        // $dat =  DB::connection('renop')
        // ->table('items as it')
        // ->join('indikators as ind','it.indikator_id','=','ind.id')
        // ->where('kode_unit','=',explode(" - ",$selectedProdi)[0] )
        // ->where('it.name','=',$namefile)
        // ->get();
        //dd($dat);
        return view('dokumen-persyaratan-pemenuhan-dokumen.create', [
            'kriteriaDokumen' => $kriteriaDokumen,
            'pemenuhanDokumen' => $pemenuhanDokumen,
            'kebutuhanDokumen' => $kebutuhanDokumen,
            'selectedProdi' => $selectedProdi,
            'allTipeDokumen' => $allTipeDokumen, // Tambahkan semua tipe dokumen
            // 'datarenop' => $dat
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kriteria_dokumen_id' => 'required|exists:kriteria_dokumen,id',
            'nama_dokumen' => 'required',
            'tambahan_informasi' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Ambil data kriteria dokumen beserta relasinya
            $kriteriaDokumen = KriteriaDokumen::with(['judulKriteriaDokumen', 'lembagaAkreditasi', 'jenjang'])
                ->findOrFail($request->kriteria_dokumen_id);

            // FIXED: Extract target prodi dari URL referer
            $targetProdi = null;
            $referer = $request->header('referer');
            if ($referer && strpos($referer, 'prodi=') !== false) {
                parse_str(parse_url($referer, PHP_URL_QUERY), $queryParams);
                $targetProdi = isset($queryParams['prodi']) ? urldecode($queryParams['prodi']) : null;
            }

            // FIXED: Role-based logic untuk menentukan prodi/fakultas
            $activeRole = session('active_role');

            // TAMBAHAN LOGGING - SEKARANG $activeRole SUDAH TERSEDIA
            if (!$targetProdi) {
                Log::warning('Target prodi tidak ditemukan dari referer', [
                    'user_id' => Auth::id(),
                    'role' => $activeRole,
                    'referer' => $referer
                ]);
            }
            
            if ($activeRole === 'Admin Prodi') {
                // Admin Prodi: selalu gunakan prodi/fakultas sendiri
                $prodiForValidation = getActiveProdi();
                $prodiForSave = getActiveProdi();
                $fakultasForSave = getActiveFakultas();
            } else {
                // Role lain (Super Admin, Admin LPM, Admin PPG, Fakultas): gunakan target prodi/fakultas
                $prodiForValidation = $targetProdi ?: getActiveProdi();
                $prodiForSave = $targetProdi ?: getActiveProdi();

                // Extract fakultas dari jadwal target prodi
                $fakultasForSave = getActiveFakultas(); // Default fallback
                $jadwalAmi = JadwalAmi::where('prodi', 'like', "%{$prodiForSave}%")
                    ->whereRaw("LEFT(periode, 4) = ?", [$kriteriaDokumen->periode_atau_tahun])
                    ->first();
                if ($jadwalAmi && $jadwalAmi->fakultas) {
                    $fakultasForSave = $jadwalAmi->fakultas;
                }
            }

            // FIXED: Validasi jadwal dengan prodi yang tepat (hanya Super Admin yang bypass)
            $periode = $kriteriaDokumen->periode_atau_tahun;
            if ($activeRole !== 'Super Admin' && !$this->isJadwalActive($prodiForValidation, $periode)) {
                throw new \Exception('Jadwal AMI untuk periode ini telah berakhir atau belum di mulai. Tidak dapat menambahkan dokumen baru.');
            }

            // Ambil kebutuhan dokumen
            $kebutuhanDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $request->kriteria_dokumen_id)
                ->where('nama_dokumen', $request->nama_dokumen)
                ->first();

            // FIXED: CEK DOKUMEN DUPLIKAT dengan prodi yang akan disave
            $existingDuplicate = PemenuhanDokumen::where('kriteria_dokumen_id', $request->kriteria_dokumen_id)
                ->where('nama_dokumen', $request->nama_dokumen)
                ->where('prodi', $prodiForSave)
                ->where('periode', $kriteriaDokumen->periode_atau_tahun)
                ->first();

            if ($existingDuplicate) {
                throw new \Exception('Dokumen dengan nama yang sama untuk periode dan program studi ini sudah ada.');
            }

            // FIXED: Hitung jumlah dokumen yang sudah ada dengan prodi yang akan disave
            $existingDocs = PemenuhanDokumen::where('kriteria_dokumen_id', $request->kriteria_dokumen_id)
                ->where('prodi', $prodiForSave)
                ->count();

            // Cek batas maksimal
            if ($existingDocs >= $kriteriaDokumen->kebutuhan_dokumen) {
                throw new \Exception('Batas maksimal dokumen telah tercapai.');
            }

            // Cek apakah ini akan menjadi file terakhir
            $isLastFile = ($existingDocs + 1) >= $kriteriaDokumen->kebutuhan_dokumen;

            // Proses file
            $fileName = null;
            if (session()->has('temp_file_' . Auth::id())) {
                $tempFile = session('temp_file_' . Auth::id());
                $tempPath = "temp/pemenuhan_dokumen/" . Auth::id() . "/{$tempFile}";
                $permanentPath = "pemenuhan_dokumen/{$tempFile}";

                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->move($tempPath, $permanentPath);
                    $fileName = $tempFile;
                }
                session()->forget('temp_file_' . Auth::id());
            }

            // FIXED: Simpan data dengan prodi/fakultas yang sudah ditentukan berdasarkan role
            $pemenuhanDokumen = PemenuhanDokumen::create([
                'kriteria_dokumen_id' => $request->kriteria_dokumen_id,
                'kriteria' => $kriteriaDokumen->judulKriteriaDokumen?->nama_kriteria_dokumen ?? 'Kriteria',
                'element' => $kriteriaDokumen->element,
                'indikator' => $kriteriaDokumen->indikator,
                'nama_dokumen' => $request->nama_dokumen,
                'tipe_dokumen' => $kebutuhanDokumen->tipe_dokumen ?? null,
                'keterangan' => $kebutuhanDokumen->keterangan ?? null,
                'file' => $fileName,
                'periode' => $kriteriaDokumen->periode_atau_tahun,
                'tambahan_informasi' => $request->tambahan_informasi,
                'prodi' => $prodiForSave,           // FIXED: Berdasarkan role logic
                'fakultas' => $fakultasForSave      // FIXED: Berdasarkan role logic
            ]);

            DB::commit();

            // Log aktivitas
            ActivityLogService::log(
                'created',
                'pemenuhan_dokumen',
                'Created new dokumen: ' . $pemenuhanDokumen->nama_dokumen . ' for prodi: ' . $prodiForSave,
                $pemenuhanDokumen,
                null,
                $pemenuhanDokumen->fresh()->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload',
                'data' => $pemenuhanDokumen,
                'isLastFile' => $isLastFile,
                'totalNeeded' => $kriteriaDokumen->kebutuhan_dokumen,
                'totalUploaded' => $existingDocs + 1
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            if (isset($fileName)) {
                Storage::disk('public')->delete("pemenuhan_dokumen/{$fileName}");
            }

            Log::error('Store pemenuhan dokumen error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        // Ambil data dokumen dengan relasinya
        $dokumen = PemenuhanDokumen::with([
            'kriteriaDokumen.judulKriteriaDokumen',
            'kriteriaDokumen.lembagaAkreditasi',
            'kriteriaDokumen.jenjang'
        ])->findOrFail($id);

        return view('dokumen-persyaratan-pemenuhan-dokumen.show', compact('dokumen'));
    }

    public function edit($id)
    {
        // Ambil data dokumen dengan relasinya
        $dokumen = PemenuhanDokumen::with([
            'kriteriaDokumen.judulKriteriaDokumen',
            'kriteriaDokumen.lembagaAkreditasi',
            'kriteriaDokumen.jenjang',
            'tipeDokumen'
        ])->findOrFail($id);

        // Ambil data kebutuhan dokumen untuk mengetahui bobot
        $kebutuhanDokumen = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $dokumen->kriteria_dokumen_id)
            ->where('nama_dokumen', $dokumen->nama_dokumen)
            ->first();

        // Ambil data penilaian kriteria jika ada
        $penilaianKriteria = PenilaianKriteria::where('kriteria_dokumen_id', $dokumen->kriteria_dokumen_id)
            ->where('prodi', $dokumen->prodi)
            ->first();

        // Ambil bobot dari kriteria dokumen
        $bobot = $dokumen->kriteriaDokumen->bobot ?? 0;

        // FIXED: Simplified jadwal query - HAPUS kondisi jenjang yang menyebabkan null
        $MengambilDataJadwal = JadwalAmi::where('prodi', 'like', "%{$dokumen->prodi}%")
            ->whereRaw("LEFT(periode, 4) = ?", [$dokumen->periode])
            ->first();

        // Jika tidak ditemukan dengan query pertama, coba dengan kode prodi saja
        if (!$MengambilDataJadwal) {
            $kodeProdi = trim(explode('-', $dokumen->prodi)[0]);
            $MengambilDataJadwal = JadwalAmi::where('prodi', 'like', "%{$kodeProdi}%")
                ->whereRaw("LEFT(periode, 4) = ?", [$dokumen->periode])
                ->first();
        }

        // Jika masih tidak ditemukan, buat object dummy untuk menghindari error
        if (!$MengambilDataJadwal) {
            $MengambilDataJadwal = (object) [
                'periode' => $dokumen->periode,
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
            ];
        }

        // Cek status penilaian
        $allowEdit = true;
        if ($penilaianKriteria && in_array($penilaianKriteria->status, [
            PenilaianKriteria::STATUS_DIAJUKAN,
            PenilaianKriteria::STATUS_DISETUJUI,
            PenilaianKriteria::STATUS_DITOLAK
        ])) {
            $allowEdit = false;
        }

        return view('dokumen-persyaratan-pemenuhan-dokumen.edit', [
            'dokumen' => $dokumen,
            'kebutuhanDokumen' => $kebutuhanDokumen,
            'MengambilDataJadwal' => $MengambilDataJadwal,
            'penilaianKriteria' => $penilaianKriteria,
            'allowEdit' => $allowEdit,
            'bobot' => $bobot
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tambahan_informasi' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:5120'
        ]);

        $kriteriaDokumenId = null;
        $prodi = null;

        try {
            DB::beginTransaction();

            $dokumen = PemenuhanDokumen::findOrFail($id);
            $kriteriaDokumenId = $dokumen->kriteria_dokumen_id;
            $prodi = $dokumen->prodi;

            // FIXED: Role-based jadwal validation
            $activeRole = session('active_role');
            $periode = $dokumen->periode;

            // Admin Prodi: cek jadwal dengan prodi sendiri
            // Role lain: cek jadwal dengan target prodi (dari dokumen yang akan diupdate)
            $prodiForValidation = ($activeRole === 'Admin Prodi') ? getActiveProdi() : $prodi;

            if ($activeRole !== 'Super Admin' && !$this->isJadwalActive($prodiForValidation, $periode)) {
                throw new \Exception('Jadwal AMI untuk periode ini telah berakhir atau belum di mulai. Tidak dapat mengubah dokumen.');
            }

            // Jika ada file baru
            if ($request->hasFile('file')) {
                // Hapus file lama jika ada
                if ($dokumen->file) {
                    Storage::disk('public')->delete("pemenuhan_dokumen/{$dokumen->file}");
                }

                // Upload file baru
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('pemenuhan_dokumen', $fileName, 'public');

                // Update nama file di database
                $dokumen->file = $fileName;
            }

            // Update info
            $dokumen->tambahan_informasi = $request->tambahan_informasi;

            $oldData = $dokumen->toArray();
            $dokumen->save();

            DB::commit();

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'pemenuhan_dokumen',
                'Updated dokumen: ' . $dokumen->nama_dokumen,
                $dokumen,
                $oldData,
                $dokumen->fresh()->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diperbarui',
                'redirect_url' => route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                    'kriteriaDokumenId' => $kriteriaDokumenId,
                    'prodi' => $prodi
                ])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Update dokumen error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'redirect_url' => route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                    'kriteriaDokumenId' => $kriteriaDokumenId,
                    'prodi' => $prodi
                ])
            ]);
        }
    }

    public function destroy($id)
    {
        $kriteriaDokumenId = null;
        $prodi = null;

        try {
            DB::beginTransaction();

            $dokumen = PemenuhanDokumen::findOrFail($id);
            $kriteriaDokumenId = $dokumen->kriteria_dokumen_id;
            $prodi = $dokumen->prodi;

            // FIXED: Role-based jadwal validation
            $activeRole = session('active_role');
            $periode = $dokumen->periode;

            // Admin Prodi: cek jadwal dengan prodi sendiri
            // Role lain: cek jadwal dengan target prodi (dari dokumen yang akan dihapus)
            $prodiForValidation = ($activeRole === 'Admin Prodi') ? getActiveProdi() : $prodi;

            if ($activeRole !== 'Super Admin' && !$this->isJadwalActive($prodiForValidation, $periode)) {
                throw new \Exception('Jadwal AMI untuk periode ini telah berakhir atau belum di mulai. Tidak dapat menghapus dokumen.');
            }

            // Cek apakah kriteria dokumen sedang dalam proses penilaian
            $penilaian = PenilaianKriteria::where('kriteria_dokumen_id', $kriteriaDokumenId)
                ->where('prodi', $prodi)
                ->first();

            // Cek apakah dokumen boleh dihapus berdasarkan status penilaian
            if ($penilaian) {
                $disallowedStatuses = [
                    PenilaianKriteria::STATUS_PENILAIAN,
                    PenilaianKriteria::STATUS_DIAJUKAN,
                    PenilaianKriteria::STATUS_DISETUJUI,
                    PenilaianKriteria::STATUS_DITOLAK,
                    PenilaianKriteria::STATUS_REVISI
                ];

                if (in_array($penilaian->status, $disallowedStatuses)) {
                    throw new \Exception('Dokumen tidak dapat dihapus karena kriteria sedang dalam status ' . strtoupper($penilaian->status));
                }
            }

            // Hapus file jika ada
            if ($dokumen->file) {
                Storage::disk('public')->delete("pemenuhan_dokumen/{$dokumen->file}");
            }

            $oldData = $dokumen->toArray();
            $dokumen->delete();

            DB::commit();

            // Log aktivitas
            ActivityLogService::log(
                'deleted',
                'pemenuhan_dokumen',
                'Deleted dokumen: ' . $dokumen->nama_dokumen,
                $dokumen,
                $oldData,
                null
            );

            return redirect()
                ->route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                    'kriteriaDokumenId' => $kriteriaDokumenId,
                    'prodi' => $prodi
                ])
                ->with('success', 'Dokumen berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Delete dokumen error: ' . $e->getMessage());

            return redirect()
                ->route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                    'kriteriaDokumenId' => $kriteriaDokumenId,
                    'prodi' => $prodi
                ])
                ->with('error', 'Gagal menghapus dokumen: ' . $e->getMessage());
        }
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:pdf,doc,docx', // 5120 KB = 5 MB
            'kriteria_dokumen_id' => 'required|exists:kriteria_dokumen,id'
        ]);

        try {
            if ($request->hasFile('file')) {
                $this->fileUploadService
                    ->setAllowedMimeTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ])
                    ->setAllowedExtensions(['pdf', 'doc', 'docx'])
                    ->setMaxFileSize(5 * 1024 * 1024) // 5 MB dalam bytes
                    ->setStoragePath('pemenuhan_dokumen');

                $result = $this->fileUploadService->upload(
                    $request->file('file'),
                    null,
                    true // Set true untuk menandai sebagai file temporary
                );

                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 422);
                }

                // Simpan nama file ke session
                session(['temp_file_' . Auth::id() => $result['filename']]);

                return response()->json([
                    'success' => true,
                    'message' => 'File berhasil diupload',
                    'filename' => $result['filename']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Upload file error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cleanupTemp()
    {
        try {
            $userId = Auth::id();
            $tempPath = "temp/pemenuhan_dokumen/{$userId}";

            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->deleteDirectory($tempPath);
            }

            session()->forget('temp_file_' . $userId);

            return response()->json([
                'success' => true,
                'message' => 'File temporary berhasil dibersihkan'
            ]);
        } catch (\Exception $e) {
            Log::error('Cleanup temp error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membersihkan file temporary'
            ], 500);
        }
    }

    public function deleteFile($id)
    {
        try {
            $pemenuhanDokumen = PemenuhanDokumen::findOrFail($id);
            // Di dalam fungsi deleteFile()
            $oldData = $pemenuhanDokumen->toArray();

            if ($pemenuhanDokumen->file) {
                Storage::disk('public')->delete("pemenuhan_dokumen/{$pemenuhanDokumen->file}");
                $pemenuhanDokumen->update(['file' => null]);
            }

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'pemenuhan_dokumen',
                'Deleted file from dokumen: ' . $pemenuhanDokumen->nama_dokumen,
                $pemenuhanDokumen,
                $oldData,
                $pemenuhanDokumen->fresh()->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete file error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
