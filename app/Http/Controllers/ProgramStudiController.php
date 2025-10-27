<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogService;
use App\Models\Jenjang;
use App\Services\FileUploadService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProgramStudiController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        // Query dasar
        $query = ProgramStudi::query();

        // Filter pencarian jika ada
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_prodi', 'like', "%{$request->search}%")
                    ->orWhere('fakultas', 'like', "%{$request->search}%");
            });
        }

        // Filter berdasarkan jenjang
        if ($request->filled('jenjang')) {
            $query->where('jenjang', $request->jenjang);
        }

        // Ambil data dengan paginasi (10 item per halaman)
        $programStudi = $query->orderBy('id', 'asc')->paginate(10);
        
        // Tambahkan ini untuk mengambil data jenjang
        $jenjangList = Jenjang::orderBy('nama')->get();

        return view('program-studi.index', compact('programStudi', 'jenjangList'));
    }

    // Modifikasi validasi
    private function validateRequest($request)
    {
        // Ambil daftar jenjang yang valid dari database
        $validJenjang = Jenjang::pluck('nama')->toArray();
        $jenjangRule = 'required|in:' . implode(',', $validJenjang);

        return $request->validate([
            'nama_prodi' => 'required',
            'jenjang' => $jenjangRule, // Ubah ini
            'fakultas' => 'required',
            'status_akreditasi' => 'nullable',
            'tanggal_kadarluarsa' => 'nullable|date',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
    
            DB::beginTransaction();
    
            $data = $validated;
    
            // Handle file yang ada di temporary
            if (session()->has('temp_bukti_filename')) {
                $tempFile = session('temp_bukti_filename');
                $userId = Auth::id();
                
                $tempPath = "temp/bukti_akreditasi/{$userId}/{$tempFile}";
                $permanentPath = "bukti_akreditasi/{$tempFile}";
    
                // Pindahkan file dari temporary ke permanent
                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->move($tempPath, $permanentPath);
                    $data['bukti'] = $tempFile;
    
                    // Bersihkan folder temporary user
                    Storage::disk('public')->deleteDirectory("temp/bukti_akreditasi/{$userId}");
                }
    
                // Hapus session
                session()->forget('temp_bukti_filename');
            }
    
            $programStudi = ProgramStudi::create($data);
    
            // Logging activity
            ActivityLogService::log(
                'created',
                'program_studi',
                'Created new Program Studi: ' . $programStudi->nama_prodi,
                $programStudi,
                null,
                $programStudi->fresh()->toArray()
            );
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Program Studi berhasil ditambahkan',
                'data' => $programStudi
            ]);
        } catch (\Exception $e) {
            DB::rollback();
    
            // Bersihkan file temporary jika ada error
            if (session()->has('temp_bukti_filename')) {
                $userId = Auth::id();
                Storage::disk('public')->deleteDirectory("temp/bukti_akreditasi/{$userId}");
                session()->forget('temp_bukti_filename');
            }
    
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, ProgramStudi $programStudi)
    {
        try {
            // Ubah validasi di sini juga
            $validJenjang = Jenjang::pluck('nama')->toArray();
            $jenjangRule = 'required|in:' . implode(',', $validJenjang);

            $request->validate([
                'nama_prodi' => 'required',
                'jenjang' => $jenjangRule, // Ubah ini
                'fakultas' => 'required',
                'status_akreditasi' => 'nullable',
                'tanggal_kadarluarsa' => 'nullable|date',
            ]);

            DB::beginTransaction();

            $oldData = $programStudi->toArray();
            $data = $request->except('_method', '_token');

            // Update data program studi
            $programStudi->update($data);

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'program_studi',
                'Updated Program Studi: ' . $programStudi->nama_prodi,
                $programStudi,
                $oldData,
                $programStudi->fresh()->toArray()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Program Studi berhasil diperbarui',
                'data' => $programStudi
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Update Program Studi error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ProgramStudi $programStudi)
    {
        try {
            $oldData = $programStudi->toArray();
            $name = $programStudi->nama_prodi;

            if ($programStudi->bukti && Storage::exists('public/bukti_akreditasi/' . $programStudi->bukti)) {
                Storage::delete('public/bukti_akreditasi/' . $programStudi->bukti);
            }

            $programStudi->delete();

            // Log aktivitas dengan format yang sama
            ActivityLogService::log(
                'deleted',
                'program_studi',
                'Deleted Program Studi: ' . $name,
                $programStudi,
                $oldData,
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'Program Studi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'bukti' => 'required|file|max:2048',
            'id' => 'nullable'
        ]);
    
        try {
            if ($request->hasFile('bukti')) {
                $this->fileUploadService
                    ->setAllowedMimeTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png'
                    ])
                    ->setAllowedExtensions(['pdf', 'jpg', 'jpeg', 'png'])
                    ->setMaxFileSize(2 * 1024 * 1024)
                    ->setStoragePath('bukti_akreditasi');
    
                // Tentukan apakah ini upload temporary
                $isTemp = $request->id === 'new';
    
                $oldFile = null;
                if (!$isTemp && $request->has('id')) {
                    $programStudi = ProgramStudi::find($request->id);
                    if ($programStudi) {
                        $oldFile = $programStudi->bukti;
                    }
                }
    
                $result = $this->fileUploadService->upload(
                    $request->file('bukti'),
                    $oldFile,
                    $isTemp
                );
    
                if ($result['success']) {
                    if ($isTemp) {
                        // Simpan nama file ke session untuk data baru
                        session(['temp_bukti_filename' => $result['filename']]);
                    } else if ($request->has('id')) {
                        $programStudi = ProgramStudi::find($request->id);
                        if ($programStudi) {
                            $oldData = $programStudi->toArray();
                            $programStudi->update(['bukti' => $result['filename']]);
    
                            ActivityLogService::log(
                                'updated',
                                'program_studi',
                                'Updated bukti for Program Studi: ' . $programStudi->nama_prodi,
                                $programStudi,
                                ['bukti' => $oldFile],
                                ['bukti' => $result['filename']]
                            );
                        }
                    }
    
                    return response()->json([
                        'success' => true,
                        'message' => 'File berhasil diupload',
                        'filename' => $result['filename'],
                        'file_url' => $result['file_url']
                    ]);
                }
    
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }
    
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada file yang diupload'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function cleanupTemp()
    {
        try {
            $userId = Auth::id();
            $tempPath = "temp/bukti_akreditasi/{$userId}";
            
            // Hapus semua file di folder temporary user
            if (Storage::disk('public')->exists($tempPath)) {
                $files = Storage::disk('public')->files($tempPath);
                foreach ($files as $file) {
                    Storage::disk('public')->delete($file);
                }
                Storage::disk('public')->deleteDirectory($tempPath);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'File temporary berhasil dibersihkan'
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal membersihkan file temporary: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membersihkan file temporary'
            ], 500);
        }
    }
}
