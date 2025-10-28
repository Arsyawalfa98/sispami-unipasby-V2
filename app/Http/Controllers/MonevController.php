<?php

namespace App\Http\Controllers;

use App\Models\KriteriaDokumen;
use App\Models\PenilaianKriteria;
use App\Models\MonevKomentar;
use App\Models\MonevGlobalKomentar;
use App\Services\MonevKts\MonevService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MonevController extends Controller
{
    protected $MonevService;

    public function __construct(
        \App\Services\PemenuhanDokumen\StatusService $statusService,
        \App\Services\PemenuhanDokumen\JadwalService $jadwalService,
        \App\Repositories\PemenuhanDokumen\KriteriaDokumenRepository $kriteriaDokumenRepo,
        \App\Repositories\PemenuhanDokumen\JadwalAmiRepository $jadwalAmiRepo
    ) {
        $this->MonevService = new \App\Services\MonevKts\MonevService(
            $statusService,
            $jadwalService,
            $kriteriaDokumenRepo,
            $jadwalAmiRepo
        );
    }

    /**
     * Halaman index untuk menampilkan daftar lembaga yang memiliki temuan KTS
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->only(['search']);
        $perPage = $request->get('per_page', 5);

        // Ambil status_temuan dari route atau request
        $statusTemuan = $request->route()->parameter('status_temuan')
            ?? $request->get('status_temuan', 'KETIDAKSESUAIAN');
        $filters['status_temuan'] = $statusTemuan;

        // Ambil Query Builder dari service
        $query = $this->MonevService->getFilteredData($filters);

        // Dapatkan semua tahun yang unik dari database
        $yearOptions = KriteriaDokumen::distinct()->pluck('periode_atau_tahun')->filter()->sort()->values()->all();

        // Dapatkan semua jenjang dari model Jenjang
        $jenjangOptions = \App\Models\Jenjang::pluck('nama', 'id')->toArray();

        // Filter berdasarkan tahun dan jenjang jika ada
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

        // --- POST-PAGINATION PROCESSING FOR filtered_details ---
        $jadwalAmiList = $this->getJadwalAmiListForFiltering($user);
        $allStatuses = collect(); // Monev doesn't use status filter in the same way, but we keep this for consistency

        // STEP 3: Batch hitung berdasarkan status_temuan
        $dataPerGrup = $this->batchCalculatePerGrup($kriteriaDokumenCollection, $statusTemuan);

        // STEP 4 & 5: Map data dengan info status_temuan dan set flag untuk button logic
        foreach ($kriteriaDokumenCollection as $item) {
            // Apply getFilteredDetailsWithInfo to each item in the paginated collection
            $item->filtered_details = collect($this->MonevService->getFilteredDetailsWithInfo($item, $jadwalAmiList, $dataPerGrup)); // Pass dataPerGrup here
            
            // Filter detail di dalam setiap item, bukan filter item keseluruhan
            if ($item->filtered_details) {
                $item->filtered_details = $item->filtered_details->filter(function($detail) {
                    $hasJadwal = !empty($detail['jadwal']);
                    $statusOk = $detail['status'] !== 'Belum ada jadwal';
                    return $hasJadwal && $statusOk;
                });
            }

            // Collect all unique statuses for dropdown (if needed for Monev)
            if ($item->filtered_details) {
                $allStatuses = $allStatuses->merge($item->filtered_details->pluck('status'));
            }

            $grupKey = $this->generateGrupKey($item);
            $totalInGroup = $dataPerGrup[$grupKey]['total'] ?? 0;

            if ($statusTemuan === 'KETIDAKSESUAIAN') {
                $item->has_kts_in_group = $totalInGroup > 0;
                $item->total_kts_in_group = $totalInGroup;
            } elseif ($statusTemuan === 'TERCAPAI') {
                $item->has_tercapai_in_group = $totalInGroup > 0;
                $item->total_tercapai_in_group = $totalInGroup;
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

        // Re-create paginator with the filtered collection
        $kriteriaDokumen = new \Illuminate\Pagination\LengthAwarePaginator(
            $kriteriaDokumenCollection->values(), // Reset keys
            $kriteriaDokumenCollection->count(), // New total count
            $perPage,
            $kriteriaDokumenPaginator->currentPage(),
            ['path' => $kriteriaDokumenPaginator->path(), 'query' => $request->query()]
        );

        // Tentukan view berdasarkan status_temuan
        return view('monev.index', compact(
            'kriteriaDokumen',
            'yearOptions',
            'jenjangOptions',
            'selectedYear',
            'selectedJenjang',
            'statusTemuan' // ← PENTING: Pass variable ini
        ));
    }

    /**
     * Halaman show group untuk menampilkan kriteria dengan temuan KTS per prodi
     */
    public function showGroup($lembagaId, $jenjangId, Request $request)
    {
        try {
            $user = Auth::user();
            $selectedProdi = $request->get('prodi');

            // ⭐ KUNCI: Ambil status_temuan
            $statusTemuan = $request->route()->parameter('status_temuan')
                ?? $request->get('status_temuan', 'KETIDAKSESUAIAN');

            // Validasi parameter
            if (!is_numeric($lembagaId) || !is_numeric($jenjangId)) {
                throw new \InvalidArgumentException('Parameter tidak valid');
            }

            // Check if lembaga and jenjang exist
            $lembaga = \App\Models\LembagaAkreditasi::find($lembagaId);
            $jenjang = \App\Models\Jenjang::find($jenjangId);

            if (!$lembaga || !$jenjang) {
                throw new \Exception('Lembaga atau jenjang tidak ditemukan');
            }

            // Ambil data dari service dengan filter status_temuan
            $data = $this->MonevService->getShowGroupData( // ← CHANGED property name
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                $user,
                $statusTemuan // ← NEW PARAMETER
            );

            // Validasi data yang diperlukan
            if (empty($data['headerData'])) {
                throw new \Exception('Data header tidak ditemukan');
            }

            if (empty($data['prodiList'])) {
                $routeName = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.index' : 'monev-kts.index';
                return redirect()->route($routeName)
                    ->with('info', 'Tidak ada program studi yang tersedia untuk kombinasi lembaga dan jenjang ini');
            }

            // Load existing comments untuk setiap kriteria jika ada prodi yang dipilih
            if ($selectedProdi && !empty($data['kriteriaDokumen'])) {
                foreach ($data['kriteriaDokumen'] as $kriteria_items) {
                    foreach ($kriteria_items as $item) {
                        // Dynamic property check
                        $penilaianProperty = 'penilaian_' . strtolower($statusTemuan);
                        if (isset($item->$penilaianProperty)) {
                            // Load komentar yang ada dengan filter status_temuan
                            $item->existing_comment = MonevKomentar::forKriteriaProdi( // ← CHANGED model name
                                $item->id,
                                $selectedProdi,
                                $statusTemuan // ← NEW PARAMETER
                            )->with('user')->first();
                        }
                    }
                }
            }

            // User permissions untuk komentar
            $userPermissions = [
                'can_comment' => $user->hasActiveRole('Auditor') || $user->hasActiveRole('Admin Prodi') || $user->hasActiveRole('Super Admin') || $user->hasActiveRole('Admin LPM'),
                'can_view_comments' => true,
            ];

            // Tentukan view berdasarkan status_temuan
            return view('monev.show-group', [
                'kriteriaDokumen' => $data['kriteriaDokumen'] ?? collect(),
                'headerData' => $data['headerData'],
                'prodiList' => $data['prodiList'],
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi,
                'currentProdiData' => $data['currentProdiData'] ?? [],
                'roleData' => $data['roleData'] ?? [],
                'userPermissions' => $userPermissions,
                'statusTemuan' => $statusTemuan // ← PENTING: Pass variable ini
            ]);
        } catch (\InvalidArgumentException $e) {
            $routeName = ($request->route()->parameter('status_temuan') ?? 'KETIDAKSESUAIAN') === 'TERCAPAI'
                ? 'monev-tercapai.index'
                : 'monev-kts.index';
            return redirect()->route($routeName)->with('error', 'Parameter tidak valid');
        } catch (\Exception $e) {
            Log::error('Monev showGroup error: ' . $e->getMessage(), [
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi,
                'user_id' => $user->id
            ]);

            $routeName = ($request->route()->parameter('status_temuan') ?? 'KETIDAKSESUAIAN') === 'TERCAPAI'
                ? 'monev-tercapai.index'
                : 'monev-kts.index';
            return redirect()->route($routeName)->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menyimpan komentar element untuk kriteria KTS (mirip update keterangan file di auditor upload)
     */
    public function storeKomentarElement(Request $request)
    {
        try {
            $validated = $request->validate([
                'kriteria_dokumen_id' => 'required|exists:kriteria_dokumen,id',
                'prodi' => 'required|string',
                'status_temuan' => 'required|in:KETIDAKSESUAIAN,TERCAPAI', // ← SUDAH ADA
                'komentar_element' => 'nullable|string|max:1000',
            ], [
                'komentar_element.max' => 'Komentar element maksimal 1000 karakter.',
                'status_temuan.required' => 'Status temuan harus diisi.',
                'status_temuan.in' => 'Status temuan tidak valid.',
            ]);

            $user = Auth::user();

            // Check permission
            if (!$user->hasActiveRole('Admin LPM') && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menambahkan komentar.'
                ], 403);
            }

            // Cari data dengan status_temuan yang sesuai
            $penilaian = PenilaianKriteria::where([
                'kriteria_dokumen_id' => $validated['kriteria_dokumen_id'],
                'prodi' => $validated['prodi'],
                'status_temuan' => $validated['status_temuan']
            ])->first();

            if (!$penilaian) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penilaian tidak ditemukan'
                ], 404);
            }

            // Get kriteria info for logging
            $kriteria = KriteriaDokumen::find($validated['kriteria_dokumen_id']);

            // Update or create komentar dengan status_temuan
            $komentar = MonevKomentar::updateOrCreate([
                'kriteria_dokumen_id' => $validated['kriteria_dokumen_id'],
                'prodi' => $validated['prodi'],
                'status_temuan' => $validated['status_temuan'],
            ], [
                'komentar_element' => $validated['komentar_element'],
                'user_id' => $user->id
            ]);

            // Log activity
            ActivityLogService::log(
                $komentar->wasRecentlyCreated ? 'created' : 'updated',
                'monev_komentar',
                "Added/updated element comment for " . $validated['status_temuan'] . " kriteria " . ($kriteria ? $kriteria->kode : '') . " - {$validated['prodi']}",
                $komentar,
                null,
                $komentar->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil disimpan.',
                'data' => [
                    'id' => $komentar->id,
                    'komentar_element' => $komentar->komentar_element,
                    'user_name' => $user->name,
                    'created_at' => $komentar->created_at->format('d/m/Y H:i:s'),
                    'updated_at' => $komentar->updated_at->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing element comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan komentar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Halaman komentar global untuk prodi tertentu
     */
    public function showGlobalKomentar($lembagaId, $jenjangId, Request $request)
    {
        try {
            $user = Auth::user();
            $selectedProdi = $request->get('prodi');

            // Ambil status_temuan dari route
            $statusTemuan = $request->route()->parameter('status_temuan')
                ?? $request->get('status_temuan', 'KETIDAKSESUAIAN');

            if (!$selectedProdi) {
                $backRoute = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.showGroup' : 'monev-kts.showGroup';
                return redirect()->route($backRoute, [
                    'lembagaId' => $lembagaId,
                    'jenjangId' => $jenjangId
                ])->with('error', 'Program studi harus dipilih.');
            }

            // Get header data
            $data = $this->MonevService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                $user,
                $statusTemuan
            );

            if (empty($data['headerData'])) {
                throw new \Exception('Data tidak ditemukan');
            }

            // Load existing global comments dengan filter status_temuan
            $globalKomentar = MonevGlobalKomentar::forGroup(
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                $data['headerData']->periode_atau_tahun,
                $statusTemuan
            )->with('admin')->latest()->get();

            $userPermissions = [
                'can_comment' => $user->hasActiveRole('Auditor') || $user->hasActiveRole('Admin Prodi') || $user->hasActiveRole('Super Admin'),
                'can_edit_delete' => $user->hasActiveRole('Super Admin'),
            ];

            return view('monev.global-komentar', compact(
                'lembagaId',
                'jenjangId',
                'selectedProdi',
                'globalKomentar',
                'userPermissions',
                'data',
                'statusTemuan'
            ));
        } catch (\Exception $e) {
            Log::error('Error in showGlobalKomentar: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $statusTemuan = $request->route()->parameter('status_temuan') ?? 'KETIDAKSESUAIAN';
            $backRoute = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.showGroup' : 'monev-kts.showGroup';

            return redirect()->route($backRoute, [
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId
            ])->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menyimpan komentar global (mirip store comment di auditor upload)
     */
    public function storeGlobalKomentar(Request $request, $lembagaId, $jenjangId)
    {
        try {
            $validated = $request->validate([
                'prodi' => 'required|string',
                'status_temuan' => 'required|in:KETIDAKSESUAIAN,TERCAPAI',
                'komentar_global' => 'required|string|max:2000',
            ], [
                'prodi.required' => 'Program studi harus diisi.',
                'komentar_global.required' => 'Komentar global harus diisi.',
                'komentar_global.max' => 'Komentar global maksimal 2000 karakter.',
                'status_temuan.required' => 'Status temuan harus diisi.',
                'status_temuan.in' => 'Status temuan tidak valid.',
            ]);

            $user = Auth::user();

            // Check permission
            if (!$user->hasActiveRole('Admin LPM') && !$user->hasActiveRole('Super Admin') && !$user->hasActiveRole('Auditor') && !$user->hasActiveRole('Admin Prodi')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menambahkan komentar global.'
                ], 403);
            }

            // Get header data for periode
            $data = $this->MonevService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $validated['prodi'],
                $user,
                $validated['status_temuan']
            );

            if (empty($data['headerData'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data header tidak ditemukan'
                ], 404);
            }

            $globalKomentar = MonevGlobalKomentar::create([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId,
                'prodi' => $validated['prodi'],
                'periode_atau_tahun' => $data['headerData']->periode_atau_tahun,
                'status_temuan' => $validated['status_temuan'],
                'komentar_global' => $validated['komentar_global'],
                'admin_id' => $user->id
            ]);

            // Log activity
            ActivityLogService::log(
                'created',
                'monev_global_komentar',
                "Added global comment for " . $validated['status_temuan'] . " monitoring {$validated['prodi']} - {$data['headerData']->periode_atau_tahun}",
                $globalKomentar,
                null,
                $globalKomentar->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar global berhasil ditambahkan.',
                'data' => [
                    'id' => $globalKomentar->id,
                    'komentar_global' => $globalKomentar->komentar_global,
                    'admin_name' => $user->name,
                    'created_at' => $globalKomentar->created_at->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing global comment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan komentar global: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update komentar global
     */
    public function updateGlobalKomentar(Request $request, $lembagaId, $jenjangId, $komentarId)
    {
        try {
            $validated = $request->validate([
                'komentar_global' => 'required|string|max:2000'
            ], [
                'komentar_global.required' => 'Komentar tidak boleh kosong.',
                'komentar_global.max' => 'Komentar maksimal 2000 karakter.'
            ]);

            $user = Auth::user();
            $komentar = MonevGlobalKomentar::where('lembaga_akreditasi_id', $lembagaId)
                ->where('jenjang_id', $jenjangId)
                ->where('id', $komentarId)
                ->firstOrFail();

            // Check permission - hanya yang membuat atau Super Admin
            if ($komentar->admin_id !== $user->id && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah komentar ini.'
                ], 403);
            }

            $oldKomentar = $komentar->komentar_global;
            $komentar->update(['komentar_global' => $validated['komentar_global']]);

            // Log activity
            ActivityLogService::log(
                'updated',
                'monev_global_komentar',
                "Updated global comment for {$komentar->status_temuan} monitoring {$komentar->prodi}",
                $komentar,
                ['komentar_global' => $oldKomentar],
                ['komentar_global' => $validated['komentar_global']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar global berhasil diperbarui.',
                'data' => [
                    'id' => $komentar->id,
                    'komentar_global' => $komentar->komentar_global,
                    'updated_at' => $komentar->updated_at->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating global comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah komentar global: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus komentar global
     */
    public function destroyGlobalKomentar($lembagaId, $jenjangId, $komentarId)
    {
        try {
            $user = Auth::user();
            $komentar = MonevGlobalKomentar::where('lembaga_akreditasi_id', $lembagaId)
                ->where('jenjang_id', $jenjangId)
                ->where('id', $komentarId)
                ->firstOrFail();

            // Check permission - hanya yang membuat atau Super Admin
            if ($komentar->admin_id !== $user->id && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus komentar ini.'
                ], 403);
            }

            $komentarText = $komentar->komentar_global;
            $prodiInfo = $komentar->prodi;
            $statusTemuan = $komentar->status_temuan;
            $komentar->delete();

            // Log activity
            ActivityLogService::log(
                'deleted',
                'monev_global_komentar',
                "Deleted global comment for {$statusTemuan} monitoring {$prodiInfo}",
                null,
                ['komentar_global' => $komentarText],
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar global berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting global comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus komentar global: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * HELPER METHODS - Copy dari yang sudah benar di index
     */
    private function batchCalculatePerGrup($kriteriaDokumenCollection, $statusTemuan = 'KETIDAKSESUAIAN')
    {
        $data = [];

        $groups = $kriteriaDokumenCollection->groupBy(function ($item) {
            return $this->generateGrupKey($item);
        });

        foreach ($groups as $grupKey => $items) {
            $firstItem = $items->first();

            $allKriteriaIds = KriteriaDokumen::where('lembaga_akreditasi_id', $firstItem->lembaga_akreditasi_id)
                ->where('jenjang_id', $firstItem->jenjang_id)
                ->where('periode_atau_tahun', $firstItem->periode_atau_tahun)
                ->pluck('id');

            $allProdiInGroup = [];
            if ($firstItem->lembagaAkreditasi && $firstItem->lembagaAkreditasi->lembagaAkreditasiDetail) {
                $allProdiInGroup = $firstItem->lembagaAkreditasi->lembagaAkreditasiDetail->pluck('prodi')->toArray();
            }

            // ⭐ Query berdasarkan status_temuan (DINAMIS)
            $perProdi = PenilaianKriteria::select('prodi', \DB::raw('COUNT(*) as count'))
                ->whereIn('kriteria_dokumen_id', $allKriteriaIds)
                ->whereIn('prodi', $allProdiInGroup)
                ->where('status_temuan', $statusTemuan) // ← Filter dinamis
                ->groupBy('prodi')
                ->pluck('count', 'prodi')
                ->toArray();

            $total = array_sum($perProdi);

            $data[$grupKey] = [
                'per_prodi' => $perProdi,
                'total' => $total
            ];
        }

        return $data;
    }

    private function generateGrupKey($item)
    {
        return $item->lembaga_akreditasi_id . '_' . $item->jenjang_id . '_' . $item->periode_atau_tahun;
    }
}
