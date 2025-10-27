<?php

namespace App\Http\Controllers;

use App\Models\JadwalAmi;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Models\LembagaAkreditasi;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class JadwalAmiController extends Controller
{
    public function index(Request $request)
    {
        $query = JadwalAmi::with('timAuditor')->latest();

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('prodi', 'like', "%{$search}%")
                    ->orWhere('fakultas', 'like', "%{$search}%")
                    ->orWhere('standar_akreditasi', 'like', "%{$search}%")
                    ->orWhereHas('timAuditor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter periode
        if ($request->filled('periode')) {
            $query->where('periode', 'like', $request->input('periode') . '%');
        }

        // Filter standar akreditasi
        if ($request->filled('standar_akreditasi')) {
            $query->where('standar_akreditasi', $request->input('standar_akreditasi'));
        }

        $jadwalAmi = $query->paginate(10);



        // Data untuk filter dropdown
        $periodes = JadwalAmi::selectRaw('DISTINCT SUBSTRING(periode, 1, 4) as tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');
        $standarAkreditasi = JadwalAmi::distinct()->orderBy('standar_akreditasi')->pluck('standar_akreditasi');

        if ($request->ajax()) {
            return view('jadwal-ami._table', compact('jadwalAmi'))->render();
        }

        return view('jadwal-ami.index', compact('jadwalAmi', 'periodes', 'standarAkreditasi'));
    }

    public function create()
    {
        $auditors = User::whereHas('roles', function ($query) {
            $query->where('name', 'Auditor');
        })
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $lembagaAkreditasi = LembagaAkreditasi::orderBy('nama')->get();

        return view('jadwal-ami.create', compact('auditors', 'lembagaAkreditasi'));
    }


    public function show(JadwalAmi $jadwalAmi)
    {
        $jadwalAmi->load('timAuditor');
        return view('jadwal-ami.show', compact('jadwalAmi'));
    }

    public function store(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'prodi' => 'required|string',
                'fakultas' => 'required|string',
                'standar_akreditasi' => 'required|exists:lembaga_akreditasi,nama',
                'periode' => [
                    'required',
                    'regex:/^\d{4}[12]$/',
                    function ($attribute, $value, $fail) {
                        $year = substr($value, 0, 4);
                        $lastDigit = substr($value, -1);
                        if ($lastDigit != '1' && $lastDigit != '2') {
                            $fail('Format periode tidak valid. Digit terakhir harus 1 atau 2.');
                        }
                        if (!is_numeric($year) || $year < 2000 || $year > 2100) {
                            $fail('Tahun tidak valid.');
                        }
                    }
                ],
                'tanggal_mulai' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        try {
                            \Carbon\Carbon::parse($value);
                        } catch (\Exception $e) {
                            $fail('Format tanggal mulai tidak valid.');
                        }
                    }
                ],
                'tanggal_selesai' => [
                    'required',
                    'date',
                    'after_or_equal:tanggal_mulai',
                    function ($attribute, $value, $fail) {
                        try {
                            \Carbon\Carbon::parse($value);
                        } catch (\Exception $e) {
                            $fail('Format tanggal selesai tidak valid.');
                        }
                    }
                ],
                'ketua_auditor' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = User::find($value);
                        if (!$user || !$user->hasRoleInDatabase('Auditor')) {
                            $fail('Ketua auditor harus memiliki role Auditor.');
                        }
                    }
                ],
                'anggota_auditor' => 'nullable|array',
                'anggota_auditor.*' => [
                    'exists:users,id',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($value == $request->ketua_auditor) {
                            $fail('Anggota auditor tidak boleh sama dengan ketua auditor.');
                        }
                        $user = User::find($value);
                        if (!$user || !$user->hasRoleInDatabase('Auditor')) {
                            $fail('Anggota auditor harus memiliki role Auditor.');
                        }
                    }
                ],

                // ===== UPLOAD SCHEDULE =====
                'upload_enabled' => 'nullable|boolean',
                'upload_mulai' => 'nullable|date|required_if:upload_enabled,1',
                'upload_selesai' => 'nullable|date|after_or_equal:upload_mulai|required_if:upload_enabled,1',
                'upload_keterangan' => 'nullable|string|max:500'
            ], [
                'required' => ':attribute harus diisi.',
                'exists' => ':attribute tidak valid.',
                'date' => 'Format :attribute tidak valid.',
                'after_or_equal' => ':attribute harus setelah atau sama dengan tanggal mulai.',
                'array' => ':attribute harus berupa array.',

                // Error messages untuk upload schedule
                'upload_mulai.required_if' => 'Tanggal mulai upload wajib diisi jika upload diaktifkan.',
                'upload_mulai.date' => 'Format tanggal mulai upload tidak valid.',
                'upload_selesai.required_if' => 'Tanggal selesai upload wajib diisi jika upload diaktifkan.',
                'upload_selesai.date' => 'Format tanggal selesai upload tidak valid.',
                'upload_selesai.after_or_equal' => 'Tanggal selesai upload harus setelah atau sama dengan tanggal mulai upload.',
                'upload_keterangan.max' => 'Keterangan upload maksimal 500 karakter.'
            ]);

            // Bersihkan nilai prodi dari "undefined -" jika ada
            $prodiValue = $validated['prodi'];
            if (strpos($prodiValue, 'undefined - ') === 0) {
                $prodiValue = str_replace('undefined - ', '', $prodiValue);
            }

            // Format tanggal
            $tanggalMulai = \Carbon\Carbon::parse($request->tanggal_mulai);
            $tanggalSelesai = \Carbon\Carbon::parse($request->tanggal_selesai);

            // Upload dates
            $uploadMulai = null;
            $uploadSelesai = null;

            if ($request->upload_enabled && $request->upload_mulai && $request->upload_selesai) {
                $uploadMulai = \Carbon\Carbon::parse($request->upload_mulai);
                $uploadSelesai = \Carbon\Carbon::parse($request->upload_selesai);
            }

            // Cek duplikasi jadwal
            $existingJadwal = JadwalAmi::where('prodi', $prodiValue)
                ->where('periode', $request->periode)
                ->first();

            if ($existingJadwal) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Jadwal AMI untuk Program Studi ini pada periode yang sama sudah ada');
            }

            DB::beginTransaction();

            try {
                // Buat jadwal AMI baru
                $jadwalAmi = JadwalAmi::create([
                    'prodi' => $prodiValue,
                    'fakultas' => $validated['fakultas'],
                    'standar_akreditasi' => $validated['standar_akreditasi'],
                    'periode' => $validated['periode'],
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'upload_enabled' => $request->upload_enabled ?? false,
                    'upload_mulai' => $uploadMulai,
                    'upload_selesai' => $uploadSelesai,
                    'upload_keterangan' => $request->upload_keterangan
                ]);

                // Attach ketua auditor
                $jadwalAmi->timAuditor()->attach($validated['ketua_auditor'], [
                    'role_auditor' => 'ketua'
                ]);

                // Attach anggota auditor jika ada
                if (!empty($validated['anggota_auditor'])) {
                    $anggotaData = [];
                    foreach ($validated['anggota_auditor'] as $anggotaId) {
                        $anggotaData[$anggotaId] = ['role_auditor' => 'anggota'];
                    }
                    $jadwalAmi->timAuditor()->attach($anggotaData);
                }

                // Log aktivitas
                ActivityLogService::log(
                    'created',
                    'jadwal_ami',
                    'Created new jadwal AMI with upload schedule for ' . $jadwalAmi->prodi,
                    $jadwalAmi,
                    null,
                    $jadwalAmi->fresh()->toArray()
                );

                DB::commit();

                return redirect()
                    ->route('jadwal-ami.index')
                    ->with('success', 'Jadwal AMI dan jadwal upload berhasil ditambahkan');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Terjadi kesalahan saat menyimpan jadwal AMI: ' . $e->getMessage());
            }
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Error creating jadwal AMI with upload schedule: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan jadwal AMI: ' . $e->getMessage());
        }
    }


    public function edit(JadwalAmi $jadwalAmi)
    {
        $auditors = User::whereHas('roles', function ($query) {
            $query->where('name', 'Auditor');
        })
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        // Dapatkan semua lembaga akreditasi
        $lembagaAkreditasi = LembagaAkreditasi::orderBy('nama')->get();
        $selectedAuditors = $jadwalAmi->timAuditor->pluck('id')->toArray();

        return view('jadwal-ami.edit', compact(
            'jadwalAmi',
            'auditors',
            'lembagaAkreditasi',
            'selectedAuditors'
        ));
    }

    public function update(Request $request, JadwalAmi $jadwalAmi)
    {
        try {
            // Pastikan lembaga akreditasi ditemukan (existing logic)
            $tahunPeriode = substr($request->periode, 0, 4);
            $lembagaAkreditasi = LembagaAkreditasi::where('nama', $request->standar_akreditasi)
                ->where('tahun', $tahunPeriode)
                ->first();

            if (!$lembagaAkreditasi) {
                return back()->withInput()
                    ->with('error', 'Lembaga Akreditasi "' . $request->standar_akreditasi .
                        '" dengan tahun ' . $tahunPeriode . ' tidak tersedia. Silakan periksa data Lembaga Akreditasi.');
            }

            $lastDigit = substr($request->periode, -1);
            if (!in_array($lastDigit, ['1', '2'])) {
                $lastDigit = '1';
            }

            $periodeConsistent = $lembagaAkreditasi->tahun . $lastDigit;
            $request->merge(['periode' => $periodeConsistent]);

            // Validasi request - GANTI DATE_FORMAT JADI DATE SEPERTI STORE
            $validated = $request->validate([
                'prodi' => 'required|string',
                'fakultas' => 'required|string',
                'standar_akreditasi' => 'required|exists:lembaga_akreditasi,nama',
                'periode' => 'required|regex:/^\d{4}[12]$/',
                'tanggal_mulai' => 'required|date_format:Y-m-d\TH:i',
                'tanggal_selesai' => 'required|date_format:Y-m-d\TH:i|after_or_equal:tanggal_mulai',
                'ketua_auditor' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = User::find($value);
                        if (!$user || !$user->hasRoleInDatabase('Auditor')) {
                            $fail('Ketua auditor harus memiliki role Auditor.');
                        }
                    }
                ],
                'anggota_auditor' => 'nullable|array',
                'anggota_auditor.*' => [
                    'exists:users,id',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($value == $request->ketua_auditor) {
                            $fail('Anggota auditor tidak boleh sama dengan ketua auditor.');
                        }
                        $user = User::find($value);
                        if (!$user || !$user->hasRoleInDatabase('Auditor')) {
                            $fail('Anggota auditor harus memiliki role Auditor.');
                        }
                    }
                ],

                // ===== UPLOAD SCHEDULE - GANTI JADI DATE SEPERTI STORE =====
                'upload_enabled' => 'nullable|boolean',
                'upload_mulai' => 'nullable|date|required_if:upload_enabled,1',
                'upload_selesai' => 'nullable|date|after_or_equal:upload_mulai|required_if:upload_enabled,1',
                'upload_keterangan' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            $oldData = $jadwalAmi->toArray();

            // Format tanggal AMI
            $tanggalMulai = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $request->tanggal_mulai);
            $tanggalSelesai = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $request->tanggal_selesai);

            // ===== UPLOAD DATES - SAMA SEPERTI STORE =====
            $uploadMulai = null;
            $uploadSelesai = null;

            if ($request->upload_enabled && $request->upload_mulai && $request->upload_selesai) {
                $uploadMulai = \Carbon\Carbon::parse($request->upload_mulai);
                $uploadSelesai = \Carbon\Carbon::parse($request->upload_selesai);
            }

            // Bersihkan nilai prodi dari "undefined -" jika ada
            $prodiValue = $validated['prodi'];
            if (strpos($prodiValue, 'undefined - ') === 0) {
                $prodiValue = str_replace('undefined - ', '', $prodiValue);
            }

            // Update data dengan upload schedule
            $updateData = [
                'prodi' => $prodiValue,
                'fakultas' => $validated['fakultas'],
                'standar_akreditasi' => $validated['standar_akreditasi'],
                'periode' => $periodeConsistent,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,

                // ===== Upload schedule =====
                'upload_enabled' => $request->upload_enabled ?? false,
                'upload_mulai' => $uploadMulai,
                'upload_selesai' => $uploadSelesai,
                'upload_keterangan' => $request->upload_keterangan
            ];

            // Update jadwal AMI
            $jadwalAmi->update($updateData);

            // Detach semua auditor lama
            $jadwalAmi->timAuditor()->detach();

            // Attach ketua auditor baru
            $jadwalAmi->timAuditor()->attach($validated['ketua_auditor'], [
                'role_auditor' => 'ketua'
            ]);

            // Attach anggota auditor baru jika ada
            if (!empty($validated['anggota_auditor'])) {
                $anggotaData = [];
                foreach ($validated['anggota_auditor'] as $anggotaId) {
                    $anggotaData[$anggotaId] = ['role_auditor' => 'anggota'];
                }
                $jadwalAmi->timAuditor()->attach($anggotaData);
            }

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'jadwal_ami',
                'Updated jadwal AMI with upload schedule for ' . $jadwalAmi->prodi,
                $jadwalAmi,
                $oldData,
                $jadwalAmi->fresh()->toArray()
            );

            DB::commit();

            return redirect()->route('jadwal-ami.index')
                ->with('success', 'Jadwal AMI dan jadwal upload berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating jadwal AMI with upload schedule: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return back()->withInput()
                ->with('error', 'Gagal memperbarui Jadwal AMI: ' . $e->getMessage());
        }
    }
    public function destroy(JadwalAmi $jadwalAmi)
    {
        try {
            $oldData = $jadwalAmi->toArray();
            $jadwalAmi->timAuditor()->detach();
            $jadwalAmi->delete();

            ActivityLogService::log(
                'deleted',
                'jadwal_ami',
                'Deleted jadwal AMI for ' . $jadwalAmi->prodi,
                $jadwalAmi,
                $oldData,
                null
            );

            return redirect()->route('jadwal-ami.index')
                ->with('success', 'Jadwal AMI berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus Jadwal AMI: ' . $e->getMessage());
        }
    }

    public function getFakultas(Request $request)
    {
        $programStudi = ProgramStudi::find($request->program_studi_id);
        return response()->json(['fakultas' => $programStudi ? $programStudi->fakultas : null]);
    }

    public function toggleUpload(Request $request, JadwalAmi $jadwalAmi)
    {
        try {
            $validated = $request->validate([
                'upload_enabled' => 'required|in:0,1,true,false' // Accept multiple formats
            ]);

            // Convert to boolean
            $uploadEnabled = in_array($validated['upload_enabled'], [1, '1', 'true', true], true);

            $oldStatus = $jadwalAmi->upload_enabled;
            $jadwalAmi->update([
                'upload_enabled' => $uploadEnabled
            ]);

            $statusText = $uploadEnabled ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLogService::log(
                'updated',
                'jadwal_ami',
                "Upload {$statusText} untuk jadwal AMI {$jadwalAmi->prodi}",
                $jadwalAmi,
                ['upload_enabled' => $oldStatus],
                ['upload_enabled' => $uploadEnabled]
            );

            return response()->json([
                'success' => true,
                'message' => "Upload berhasil {$statusText}",
                'upload_enabled' => $uploadEnabled,
                'upload_status' => $jadwalAmi->fresh()->upload_status,
                'upload_badge' => $jadwalAmi->fresh()->upload_status_badge
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUploadStats(JadwalAmi $jadwalAmi)
    {
        try {
            $stats = [
                'jadwal_info' => [
                    'prodi' => $jadwalAmi->prodi,
                    'periode' => $jadwalAmi->periode,
                    'upload_enabled' => $jadwalAmi->upload_enabled,
                    'upload_status' => $jadwalAmi->upload_status,
                    'upload_badge' => $jadwalAmi->upload_status_badge
                ],
                'upload_schedule' => [
                    'upload_mulai' => $jadwalAmi->upload_mulai ? $jadwalAmi->upload_mulai->format('Y-m-d H:i:s') : null,
                    'upload_selesai' => $jadwalAmi->upload_selesai ? $jadwalAmi->upload_selesai->format('Y-m-d H:i:s') : null,
                    'is_active' => $jadwalAmi->isUploadActive(),
                    'is_started' => $jadwalAmi->isUploadStarted(),
                    'is_ended' => $jadwalAmi->isUploadEnded()
                ],
                'statistics' => [
                    'total_files' => $jadwalAmi->auditorUploads()->count(),
                    'total_auditors' => $jadwalAmi->timAuditor()->count(),
                    'auditors_uploaded' => $jadwalAmi->auditorUploads()->distinct('auditor_id')->count(),
                    'total_file_size' => $jadwalAmi->auditorUploads()->sum('file_size')
                ],
                'recent_uploads' => $jadwalAmi->auditorUploads()
                    ->with('auditor:id,name')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function ($upload) {
                        return [
                            'id' => $upload->id,
                            'original_name' => $upload->original_name,
                            'auditor_name' => $upload->auditor->name ?? 'Unknown',
                            'uploaded_at' => $upload->uploaded_at->format('Y-m-d H:i:s'),
                            'file_size_formatted' => $this->formatFileSize($upload->file_size)
                        ];
                    }),
                'comments' => $jadwalAmi->uploadComments()
                    ->with('admin:id,name')
                    ->latest()
                    ->take(3)
                    ->get()
                    ->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'komentar' => $comment->komentar,
                            'admin_name' => $comment->admin->name ?? 'Unknown',
                            'created_at' => $comment->created_at->format('Y-m-d H:i:s')
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik upload: ' . $e->getMessage()
            ], 500);
        }
    }

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

    private function parseFlexibleDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Daftar format tanggal yang mungkin
        $formats = [
            'Y-m-d\TH:i',      // HTML5 datetime-local format
            'Y-m-d\TH:i:s',    // HTML5 datetime-local with seconds
            'Y-m-d H:i:s',     // Database datetime format
            'Y-m-d H:i',       // Simple datetime format
            'd/m/Y H:i',       // Indonesian format
            'd-m-Y H:i',       // Alternative Indonesian format
        ];

        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $dateString);
                if ($date && $date->format($format) === $dateString) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: try Carbon's general parsing
        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
