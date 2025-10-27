<?php

namespace App\Http\Controllers;

use App\Models\JadwalAmi;
use App\Models\AuditorUpload;
use App\Models\AuditorUploadComment;
use App\Models\User;
use App\Models\KriteriaDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\PemenuhanDokumen\PemenuhanDokumenService;

class AuditorUploadController extends Controller
{
    protected $pemenuhanDokumenService;

    public function __construct(PemenuhanDokumenService $pemenuhanDokumenService)
    {
        $this->pemenuhanDokumenService = $pemenuhanDokumenService;
    }

    /**
     * Display a listing of lembaga akreditasi with upload-enabled jadwal AMI
     * Mengadopsi logic dari PemenuhanDokumenController@index
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->only(['search']);
        $perPage = $request->get('per_page', 10);

        // Ambil data dari service (sama seperti pemenuhan dokumen)
        $kriteriaDokumen = $this->pemenuhanDokumenService->getFilteredData($filters, $perPage);

        // Enhance filtered_details dengan upload schedule information
        foreach ($kriteriaDokumen as $item) {
            if ($item->filtered_details) {
                $item->filtered_details = $item->filtered_details->map(function ($detail) {
                    // Cari jadwal AMI yang sesuai
                    $jadwal = null;
                    if (isset($detail['jadwal']) && $detail['jadwal']) {
                        $jadwalData = $detail['jadwal'];
                        $jadwal = JadwalAmi::where('prodi', $detail['prodi'])
                            ->where('periode', 'like', substr($jadwalData['periode'] ?? date('Y'), 0, 4) . '%')
                            ->first();
                    }

                    if ($jadwal) {
                        // Add upload schedule information
                        $detail['upload_enabled'] = $jadwal->upload_enabled ?? false;
                        $detail['upload_status'] = $jadwal->upload_status ?? 'Upload Tidak Diaktifkan';
                        $detail['upload_status_badge'] = $jadwal->upload_status_badge ?? 'badge-secondary';
                        $detail['upload_mulai'] = $jadwal->upload_mulai;
                        $detail['upload_selesai'] = $jadwal->upload_selesai;
                        $detail['can_access_upload'] = $jadwal->upload_enabled &&
                            $jadwal->upload_mulai &&
                            $jadwal->upload_selesai;

                        // Upload statistics
                        $detail['upload_stats'] = [
                            'total_files' => $jadwal->auditorUploads()->count(),
                            'total_auditors' => $jadwal->timAuditor()->count(),
                            'auditors_uploaded' => $jadwal->auditorUploads()->distinct('auditor_id')->count()
                        ];

                        // Jadwal ID untuk button action
                        $detail['jadwal_ami_id'] = $jadwal->id;
                    } else {
                        // Default values jika tidak ada jadwal
                        $detail['upload_enabled'] = false;
                        $detail['upload_status'] = 'Belum Ada Jadwal Upload';
                        $detail['upload_status_badge'] = 'badge-secondary';
                        $detail['upload_mulai'] = null;
                        $detail['upload_selesai'] = null;
                        $detail['can_access_upload'] = false;
                        $detail['upload_stats'] = [
                            'total_files' => 0,
                            'total_auditors' => 0,
                            'auditors_uploaded' => 0
                        ];
                        $detail['jadwal_ami_id'] = null;
                    }

                    return $detail;
                });
            }
        }

        // Filter hanya yang memiliki upload enabled (optional - bisa dihapus jika ingin tampil semua)
        foreach ($kriteriaDokumen as $item) {
            if ($item->filtered_details) {
                $item->filtered_details = $item->filtered_details->filter(function ($detail) {
                    // Tampilkan semua, tapi prioritaskan yang upload_enabled
                    return true; // atau return $detail['upload_enabled']; jika hanya mau yang enabled
                });
            }
        }

        // Get filter options untuk dropdown (sebelum filtering untuk mendapatkan semua opsi)
        $statusOptions = $this->getUniqueUploadStatuses($kriteriaDokumen);
        $yearOptions = $this->getUniqueYears($kriteriaDokumen);
        $jenjangOptions = \App\Models\Jenjang::pluck('nama', 'id')->toArray();

        // Apply additional filters
        $selectedStatus = $request->get('status');
        $selectedYear = $request->get('year');
        $selectedJenjang = $request->get('jenjang');

        if ($selectedStatus && $selectedStatus !== 'all') {
            $kriteriaDokumen = $this->filterByUploadStatus($kriteriaDokumen, $selectedStatus);
        }

        if ($selectedYear && $selectedYear !== 'all') {
            $kriteriaDokumen = $this->filterByYear($kriteriaDokumen, $selectedYear);
        }

        if ($selectedJenjang && $selectedJenjang !== 'all') {
            $kriteriaDokumen = $this->filterByJenjang($kriteriaDokumen, $selectedJenjang);
        }

        // Remove items tanpa filtered_details yang valid (after all filters)
        $filteredCollection = $kriteriaDokumen->getCollection()->filter(function ($item) {
            return $item->filtered_details && $item->filtered_details->count() > 0;
        });

        // Recreate paginator dengan collection yang sudah difilter
        $kriteriaDokumen = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredCollection,
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            [
                'path' => $kriteriaDokumen->path(),
                'pageName' => 'page',
            ]
        );

        // Preserve query parameters untuk pagination
        $kriteriaDokumen->appends(request()->query());

        return view('auditor-upload.index', compact(
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
     * Get unique upload statuses
     */
    private function getUniqueUploadStatuses($kriteriaDokumen)
    {
        $statuses = collect();

        foreach ($kriteriaDokumen as $item) {
            if ($item->filtered_details) {
                foreach ($item->filtered_details as $detail) {
                    if (isset($detail['upload_status'])) {
                        $statuses->push($detail['upload_status']);
                    }
                }
            }
        }

        return $statuses->unique()->values()->all();
    }

    /**
     * Get unique years
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
     * Filter by upload status
     */
    private function filterByUploadStatus($kriteriaDokumen, $status)
    {
        $filteredCollection = $kriteriaDokumen->getCollection()->filter(function ($item) use ($status) {
            if (!$item->filtered_details) return false;

            $filteredDetails = $item->filtered_details->filter(function ($detail) use ($status) {
                return isset($detail['upload_status']) && $detail['upload_status'] === $status;
            });

            if ($filteredDetails->isNotEmpty()) {
                $item->filtered_details = $filteredDetails;
                return true;
            }

            return false;
        });

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredCollection,
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );
    }

    /**
     * Filter by year
     */
    private function filterByYear($kriteriaDokumen, $year)
    {
        $filteredCollection = $kriteriaDokumen->getCollection()->filter(function ($item) use ($year) {
            return isset($item->periode_atau_tahun) && $item->periode_atau_tahun == $year;
        });

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredCollection,
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );
    }

    /**
     * Filter by jenjang
     */
    private function filterByJenjang($kriteriaDokumen, $jenjangNama)
    {
        $filteredCollection = $kriteriaDokumen->getCollection()->filter(function ($item) use ($jenjangNama) {
            return isset($item->jenjang) && $item->jenjang->nama === $jenjangNama;
        });

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredCollection,
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );
    }

    // ... rest of the methods remain unchanged (showGroup, store, download, etc.)

    /**
     * Show group - Detail jadwal dengan upload interface
     * Sama seperti PemenuhanDokumenController@showGroup
     */
    public function showGroup($jadwalId, Request $request)
    {
        try {
            $user = Auth::user();

            // Ambil jadwal AMI
            $jadwal = JadwalAmi::with(['timAuditor', 'auditorUploads.auditor', 'uploadComments.admin'])
                ->findOrFail($jadwalId);

            // Flexible access control - berdasarkan permission saja
            // Tidak ada hard restriction, biarkan middleware permission yang handle

            // Check permission berdasarkan role untuk filtering data
            $canViewAll = $user->hasActiveRole('Super Admin') || $user->hasActiveRole('Admin LPM');

            if (!$canViewAll) {
                // Role terbatas - cek apakah ada akses ke jadwal ini
                if ($user->hasActiveRole('Auditor')) {
                    // Auditor hanya bisa lihat jadwal yang ditugaskan
                    if (!$jadwal->isUserAssignedAsAuditor($user->id)) {
                        return redirect()->route('auditor-upload.index')
                            ->with('error', 'Anda tidak memiliki akses ke jadwal ini.');
                    }
                } elseif ($user->hasActiveRole('Admin Prodi')) {
                    // Admin Prodi hanya untuk prodi mereka
                    $userProdi = getActiveProdi();
                    $kodeProdi = trim(explode('-', $userProdi)[0]);
                    if (!str_starts_with($jadwal->prodi, $kodeProdi)) {
                        return redirect()->route('auditor-upload.index')
                            ->with('error', 'Anda tidak memiliki akses ke jadwal ini.');
                    }
                } elseif ($user->hasActiveRole('Fakultas')) {
                    // Role Fakultas untuk fakultas mereka
                    if ($jadwal->fakultas !== $user->fakultas) {
                        return redirect()->route('auditor-upload.index')
                            ->with('error', 'Anda tidak memiliki akses ke jadwal ini.');
                    }
                }
                // Role lain dengan permission bisa akses (fleksibel untuk masa depan)
            }

            // Kelompokkan uploads berdasarkan auditor
            $uploadsByAuditor = $jadwal->auditorUploads()
                ->with('auditor')
                ->get()
                ->groupBy('auditor_id');

            // Info tim auditor
            $timAuditor = [
                'ketua' => $jadwal->timAuditor->where('pivot.role_auditor', 'ketua')->first(),
                'anggota' => $jadwal->timAuditor->where('pivot.role_auditor', 'anggota')
            ];

            // Status dan permission untuk user (lebih fleksibel)
            $userPermissions = [
                'can_upload' => $jadwal->canUserUpload($user->id),
                'can_comment' => $user->hasActiveRole('Admin LPM') || $user->hasActiveRole('Super Admin'),
                'can_download' => true, // Semua yang bisa akses halaman bisa download
                'is_assigned_auditor' => $jadwal->isUserAssignedAsAuditor($user->id),
                'is_super_admin' => $user->hasActiveRole('Super Admin'),
                'is_admin_lpm' => $user->hasActiveRole('Admin LPM'),
                'can_view_all' => $canViewAll
            ];

            // Upload statistics (tanpa progress bar, hanya info)
            $uploadStats = [
                'total_files' => $jadwal->auditorUploads->count(),
                'total_size' => $jadwal->auditorUploads->sum('file_size'),
                'total_auditors' => $jadwal->timAuditor->count()
            ];

            return view('auditor-upload.show-group', compact(
                'jadwal',
                'uploadsByAuditor',
                'timAuditor',
                'userPermissions',
                'uploadStats'
            ));
        } catch (\Exception $e) {
            Log::error('Error in AuditorUploadController@showGroup: ' . $e->getMessage());
            return redirect()->route('auditor-upload.index')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function showGroupByLembaga(Request $request, $lembagaId, $jenjangId)
    {
        try {
            $user = Auth::user();
            $selectedProdi = $request->get('prodi');

            // 1. Gunakan service untuk mendapatkan data yang sudah terfilter dengan benar
            $serviceData = $this->pemenuhanDokumenService->getShowGroupData($lembagaId, $jenjangId, $selectedProdi, $user);
            $prodiList = $serviceData['prodiList'];
            $headerData = $serviceData['headerData'];

            if (!$headerData || $prodiList->isEmpty()) {
                return redirect()->route('auditor-upload.index')->with('error', 'Tidak ada jadwal yang tersedia untuk grup ini.');
            }

            // 2. Jika prodi sudah dipilih dari dropdown, cari jadwal ID-nya dan redirect
            if ($selectedProdi) {
                $jadwal = JadwalAmi::where('prodi', $selectedProdi)
                    ->where('standar_akreditasi', $headerData->lembagaAkreditasi->nama)
                    ->where('periode', 'like', $headerData->periode_atau_tahun . '%')
                    ->first();

                if ($jadwal) {
                    return redirect()->route('auditor-upload.showGroup', ['jadwalId' => $jadwal->id]);
                } else {
                    return back()->with('error', 'Jadwal untuk prodi yang dipilih tidak ditemukan.')->withInput();
                }
            }

            // 3. Jika belum ada prodi yang dipilih, tampilkan halaman pemilihan
            return view('auditor-upload.select-prodi', [
                'prodiList' => $prodiList,
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'headerData' => $headerData,
                'filterParams' => $request->only([
                    'status', 'year', 'jenjang'
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Error in AuditorUploadController@showGroupByLembaga: ' . $e->getMessage());
            return redirect()->route('auditor-upload.index')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Semua method lainnya tetap sama (store, download, view, destroy, storeComment, dll)
    public function store(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'jadwal_ami_id' => 'required|exists:jadwal_ami,id',
                'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
                'keterangan' => 'nullable|string|max:500'
            ], [
                'file.required' => 'File harus dipilih.',
                'file.mimes' => 'File harus berformat PDF, DOC, atau DOCX.',
                'file.max' => 'Ukuran file maksimal 10MB.',
                'keterangan.max' => 'Keterangan maksimal 500 karakter.'
            ]);

            $user = Auth::user();
            $jadwal = JadwalAmi::findOrFail($validated['jadwal_ami_id']);
            $file = $request->file('file');
            $realPath = $file->getRealPath();
            $this->validateFileSecurity($realPath, $file);

            // Check permission dengan logic baru dari model
            if (!$jadwal->canUserUpload($user->id)) {
                throw ValidationException::withMessages([
                    'file' => ['Anda tidak dapat upload file saat ini. Periksa jadwal upload dan pastikan Anda memiliki akses yang sesuai.']
                ]);
            }

            DB::beginTransaction();

            try {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $fileExtension = $file->getClientOriginalExtension();
                $fileSize = $file->getSize();

                // Generate unique filename
                $storedName = time() . '_' . Str::random(10) . '.' . $fileExtension;

                // Store file di storage/app/public/auditor_uploads
                $filePath = $file->storeAs('auditor_uploads', $storedName, 'public');

                // Simpan record ke database
                $upload = AuditorUpload::create([
                    'jadwal_ami_id' => $validated['jadwal_ami_id'],
                    'auditor_id' => $user->id,
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_type' => $file->getClientMimeType(),
                    'keterangan' => $request->keterangan,
                    'uploaded_at' => now()
                ]);

                // Log activity dengan context yang tepat
                $roleContext = '';
                if ($user->hasActiveRole('Super Admin')) {
                    $roleContext = ' (Super Admin mode)';
                } elseif ($user->hasActiveRole('Admin LPM')) {
                    $roleContext = ' (Admin LPM mode)';
                } elseif ($user->hasActiveRole('Auditor')) {
                    $roleContext = ' (Auditor assigned)';
                }

                ActivityLogService::log(
                    'created',
                    'auditor_upload',
                    "Uploaded file {$originalName} for jadwal AMI {$jadwal->prodi}{$roleContext}",
                    $upload,
                    null,
                    $upload->toArray()
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'File berhasil diupload.',
                    'data' => [
                        'id' => $upload->id,
                        'original_name' => $upload->original_name,
                        'file_size_formatted' => $this->formatFileSize($upload->file_size),
                        'uploaded_at' => $upload->uploaded_at->format('d/m/Y H:i:s'),
                        'uploaded_by' => $user->name,
                        'role_context' => trim($roleContext, ' ()')
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                // Hapus file jika sudah terupload tapi gagal save ke database
                if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error uploading file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file
     */
    public function download($id)
    {
        try {
            $upload = AuditorUpload::with('jadwalAmi')->findOrFail($id);
            $user = Auth::user();

            // Check permission untuk download
            $jadwal = $upload->jadwalAmi;

            if ($user->hasActiveRole('Auditor')) {
                // Auditor hanya bisa download dari jadwal yang ditugaskan
                if (!$jadwal->isUserAssignedAsAuditor($user->id)) {
                    abort(403, 'Anda tidak memiliki akses untuk download file ini.');
                }
            } elseif ($user->hasActiveRole('Admin Prodi')) {
                // Admin Prodi hanya untuk prodi mereka
                $userProdi = $user->prodi;
                $kodeProdi = trim(explode('-', $userProdi)[0]);
                if (!str_starts_with($jadwal->prodi, $kodeProdi)) {
                    abort(403, 'Anda tidak memiliki akses untuk download file ini.');
                }
            } elseif ($user->hasActiveRole('Fakultas')) {
                // Role Fakultas untuk fakultas mereka
                if ($jadwal->fakultas !== $user->fakultas) {
                    abort(403, 'Anda tidak memiliki akses untuk download file ini.');
                }
            }

            // Check apakah file ada
            if (!Storage::disk('public')->exists($upload->file_path)) {
                return redirect()->back()->with('error', 'File tidak ditemukan.');
            }

            // Log download activity
            ActivityLogService::log(
                'downloaded',
                'auditor_upload',
                "Downloaded file {$upload->original_name} by {$user->name}",
                $upload,
                null,
                ['downloaded_by' => $user->name, 'downloaded_at' => now()]
            );

            return Storage::disk('public')->download($upload->file_path, $upload->original_name);
        } catch (\Exception $e) {
            Log::error('Error downloading file: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat download file.');
        }
    }

    /**
     * View file (untuk PDF)
     */
    public function view($id)
    {
        try {
            $upload = AuditorUpload::with('jadwalAmi')->findOrFail($id);
            $user = Auth::user();

            // Check permission (sama seperti download)
            $jadwal = $upload->jadwalAmi;

            if ($user->hasActiveRole('Auditor')) {
                if (!$jadwal->isUserAssignedAsAuditor($user->id)) {
                    abort(403, 'Anda tidak memiliki akses untuk melihat file ini.');
                }
            } elseif ($user->hasActiveRole('Admin Prodi')) {
                $userProdi = $user->prodi;
                $kodeProdi = trim(explode('-', $userProdi)[0]);
                if (!str_starts_with($jadwal->prodi, $kodeProdi)) {
                    abort(403, 'Anda tidak memiliki akses untuk melihat file ini.');
                }
            } elseif ($user->hasActiveRole('Fakultas')) {
                if ($jadwal->fakultas !== $user->fakultas) {
                    abort(403, 'Anda tidak memiliki akses untuk melihat file ini.');
                }
            }

            // Hanya support PDF untuk view
            if (!str_contains($upload->file_type, 'pdf')) {
                return redirect()->back()->with('error', 'Preview hanya tersedia untuk file PDF.');
            }

            // Check apakah file ada
            if (!Storage::disk('public')->exists($upload->file_path)) {
                return redirect()->back()->with('error', 'File tidak ditemukan.');
            }

            $fileUrl = Storage::disk('public')->url($upload->file_path);

            return view('auditor-upload.view-file', compact('upload', 'fileUrl'));
        } catch (\Exception $e) {
            Log::error('Error viewing file: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuka file.');
        }
    }

    /**
     * Delete uploaded file
     */
    public function destroy($id)
    {
        try {
            $upload = AuditorUpload::with('jadwalAmi')->findOrFail($id);
            $user = Auth::user();
            $jadwal = $upload->jadwalAmi;

            // Check permission - hanya auditor yang upload atau admin yang bisa hapus
            if ($user->hasActiveRole('Auditor')) {
                // Auditor hanya bisa hapus file sendiri dan masih dalam periode upload
                if ($upload->auditor_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda hanya dapat menghapus file yang Anda upload sendiri.'
                    ], 403);
                }

                if (!$jadwal->isUploadActive()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat menghapus file karena periode upload sudah berakhir.'
                    ], 403);
                }
            } elseif (!$user->hasActiveRole('Admin LPM') && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus file ini.'
                ], 403);
            }

            DB::beginTransaction();

            try {
                $originalName = $upload->original_name;
                $filePath = $upload->file_path;

                // Hapus record dari database
                $upload->delete();

                // Hapus file dari storage
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                // Log activity
                ActivityLogService::log(
                    'deleted',
                    'auditor_upload',
                    "Deleted file {$originalName} from jadwal AMI {$jadwal->prodi}",
                    null,
                    $upload->toArray(),
                    null
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'File berhasil dihapus.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error deleting file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store comment from Admin LPM
     */
    public function storeComment(Request $request, $jadwalId)
    {
        try {
            $validated = $request->validate([
                'komentar' => 'required|string|max:1000'
            ], [
                'komentar.required' => 'Komentar harus diisi.',
                'komentar.max' => 'Komentar maksimal 1000 karakter.'
            ]);

            $user = Auth::user();
            $jadwal = JadwalAmi::findOrFail($jadwalId);

            // Check permission - hanya Admin LPM dan Super Admin
            if (!$user->hasActiveRole('Admin LPM') && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menambahkan komentar.'
                ], 403);
            }

            $comment = AuditorUploadComment::create([
                'jadwal_ami_id' => $jadwalId,
                'admin_id' => $user->id,
                'komentar' => $validated['komentar']
            ]);

            // Log activity
            ActivityLogService::log(
                'created',
                'auditor_upload_comment',
                "Added comment for jadwal AMI {$jadwal->prodi}",
                $comment,
                null,
                $comment->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil ditambahkan.',
                'data' => [
                    'id' => $comment->id,
                    'komentar' => $comment->komentar,
                    'admin_name' => $user->name,
                    'created_at' => $comment->created_at->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan komentar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update comment
     */
    public function updateComment(Request $request, $jadwalId, $commentId)
    {
        try {
            $validated = $request->validate([
                'komentar' => 'required|string|max:1000'
            ]);

            $user = Auth::user();
            $comment = AuditorUploadComment::where('jadwal_ami_id', $jadwalId)
                ->where('id', $commentId)
                ->firstOrFail();

            // Check permission - hanya yang membuat comment atau Super Admin
            if ($comment->admin_id !== $user->id && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah komentar ini.'
                ], 403);
            }

            $oldComment = $comment->komentar;
            $comment->update(['komentar' => $validated['komentar']]);

            // Log activity
            ActivityLogService::log(
                'updated',
                'auditor_upload_comment',
                "Updated comment for jadwal AMI",
                $comment,
                ['komentar' => $oldComment],
                ['komentar' => $validated['komentar']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil diperbarui.',
                'data' => [
                    'id' => $comment->id,
                    'komentar' => $comment->komentar,
                    'updated_at' => $comment->updated_at->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah komentar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete comment
     */
    public function destroyComment($jadwalId, $commentId)
    {
        try {
            $user = Auth::user();
            $comment = AuditorUploadComment::where('jadwal_ami_id', $jadwalId)
                ->where('id', $commentId)
                ->firstOrFail();

            // Check permission - hanya yang membuat comment atau Super Admin
            if ($comment->admin_id !== $user->id && !$user->hasActiveRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus komentar ini.'
                ], 403);
            }

            $commentText = $comment->komentar;
            $comment->delete();

            // Log activity
            ActivityLogService::log(
                'deleted',
                'auditor_upload_comment',
                "Deleted comment for jadwal AMI",
                null,
                ['komentar' => $commentText],
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus komentar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk download files
     */
    public function bulkDownload($jadwalId)
    {
        try {
            $user = Auth::user();
            $jadwal = JadwalAmi::with('auditorUploads')->findOrFail($jadwalId);

            // Check permission
            if ($user->hasActiveRole('Auditor')) {
                if (!$jadwal->isUserAssignedAsAuditor($user->id)) {
                    abort(403, 'Anda tidak memiliki akses untuk download file dari jadwal ini.');
                }
            } elseif ($user->hasActiveRole('Admin Prodi')) {
                $userProdi = $user->prodi;
                $kodeProdi = trim(explode('-', $userProdi)[0]);
                if (!str_starts_with($jadwal->prodi, $kodeProdi)) {
                    abort(403, 'Anda tidak memiliki akses untuk download file dari jadwal ini.');
                }
            } elseif ($user->hasActiveRole('Fakultas')) {
                if ($jadwal->fakultas !== $user->fakultas) {
                    abort(403, 'Anda tidak memiliki akses untuk download file dari jadwal ini.');
                }
            }

            if ($jadwal->auditorUploads->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada file untuk didownload.');
            }

            // Create temporary zip file
            $zipFileName = 'Auditor_Upload_' . Str::slug($jadwal->prodi) . '_' . date('Y-m-d') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                return redirect()->back()->with('error', 'Gagal membuat file zip.');
            }

            foreach ($jadwal->auditorUploads as $upload) {
                $filePath = storage_path('app/public/' . $upload->file_path);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $upload->auditor->name . '_' . $upload->original_name);
                }
            }

            $zip->close();

            // Log activity
            ActivityLogService::log(
                'bulk_downloaded',
                'auditor_upload',
                "Bulk downloaded files from jadwal AMI {$jadwal->prodi} by {$user->name}",
                $jadwal,
                null,
                ['downloaded_by' => $user->name, 'file_count' => $jadwal->auditorUploads->count()]
            );

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error bulk downloading files: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat download file.');
        }
    }

    /**
     * Helper method to format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function updateKeterangan(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'keterangan' => 'nullable|string|max:500'
            ], [
                'keterangan.max' => 'Keterangan maksimal 500 karakter.'
            ]);

            $user = Auth::user();
            $upload = AuditorUpload::with('jadwalAmi')->findOrFail($id);

            // Check permission - hanya yang upload file atau admin yang bisa edit keterangan
            if (
                $upload->auditor_id !== $user->id &&
                !$user->hasActiveRole('Admin LPM') &&
                !$user->hasActiveRole('Super Admin')
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah keterangan file ini.'
                ], 403);
            }

            $oldKeterangan = $upload->keterangan;
            $upload->update([
                'keterangan' => $validated['keterangan']
            ]);

            // Log activity
            ActivityLogService::log(
                'updated',
                'auditor_upload',
                "Updated keterangan for file {$upload->original_name} in jadwal AMI {$upload->jadwalAmi->prodi}",
                $upload,
                ['keterangan' => $oldKeterangan],
                ['keterangan' => $validated['keterangan']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Keterangan berhasil diperbarui.',
                'data' => [
                    'id' => $upload->id,
                    'keterangan' => $upload->keterangan,
                    'updated_at' => $upload->updated_at->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating keterangan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah keterangan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validateFileSecurity($filePath, $file)
    {
        // 1. Validasi MIME type real (bukan dari client)
        $realMimeType = mime_content_type($filePath);
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($realMimeType, $allowedMimes)) {
            throw ValidationException::withMessages([
                'file' => ['Jenis file tidak valid berdasarkan konten file.']
            ]);
        }

        // 2. Scan header file untuk pattern berbahaya
        $fileContent = file_get_contents($filePath, false, null, 0, 1024); // Read first 1KB
        $dangerousPatterns = [
            '<?php',
            '<%',
            '<script',
            'eval(',
            'system(',
            'exec(',
            'shell_exec',
            'passthru',
            'file_get_contents',
            'curl_exec'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($fileContent, $pattern) !== false) {
                throw ValidationException::withMessages([
                    'file' => ['File mengandung konten yang tidak diizinkan.']
                ]);
            }
        }

        // 3. Validasi ekstensi file asli
        $fileInfo = new \SplFileInfo($file->getClientOriginalName());
        $extension = strtolower($fileInfo->getExtension());
        if (!in_array($extension, ['pdf', 'doc', 'docx'])) {
            throw ValidationException::withMessages([
                'file' => ['Ekstensi file tidak diizinkan.']
            ]);
        }
    }
}
