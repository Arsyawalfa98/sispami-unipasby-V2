<?php

namespace App\Http\Controllers;

use App\Models\DokumenSPMIAMI;
use App\Models\KategoriDokumen;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class DokumenSPMIAMIController extends Controller
{
    //index lama menampilkan seluruhnya
    // public function index(Request $request)
    // {
    //     $query = DokumenSPMIAMI::query();

    //     // Filter berdasarkan pencarian
    //     if ($request->filled('search')) {
    //         $query->where(function ($q) use ($request) {
    //             $q->where('kategori_dokumen', 'like', "%{$request->search}%")
    //                 ->orWhere('nama_dokumen', 'like', "%{$request->search}%");
    //         });
    //     }

    //     $dokumens = $query->paginate(10);

    //     return view('dokumen-spmi-ami.index', compact('dokumens'));
    // }
    //index lama menampilkan seluruhnya

    public function index(Request $request)
    {
        $userRoles = Auth::user()->roles->pluck('id')->toArray();

        // Get allowed kategori for user
        $allowedKategori = KategoriDokumen::whereHas('roles', function ($query) use ($userRoles) {
            $query->whereIn('roles.id', $userRoles);
        })->pluck('nama_kategori');

        $query = DokumenSPMIAMI::query()
            ->whereIn('kategori_dokumen', $allowedKategori);

        // Filter pencarian
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('kategori_dokumen', 'like', "%{$request->search}%")
                    ->orWhere('nama_dokumen', 'like', "%{$request->search}%");
            });
        }

        $dokumens = $query->with('kategori.roles')->paginate(10);

        return view('dokumen-spmi-ami.index', compact('dokumens'));
    }

    public function show(DokumenSPMIAMI $dokumenSPMIAMI)
    {
        return view('dokumen-spmi-ami.show', compact('dokumenSPMIAMI'));
    }

    public function create()
    {
        $userRoles = Auth::user()->roles->pluck('id')->toArray();
        $kategoriList = KategoriDokumen::whereHas('roles', function ($query) use ($userRoles) {
            $query->whereIn('roles.id', $userRoles);
        })->get();

        return view('dokumen-spmi-ami.create', compact('kategoriList'));
    }

    public function uploadTemp(Request $request)
    {
        try {
            $uploadService = new FileUploadService();
            $uploadService
                ->setAllowedMimeTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ])
                ->setAllowedExtensions(['pdf', 'doc', 'docx'])
                ->setMaxFileSize(10 * 1024 * 1024) // 10MB
                ->setStoragePath('dokumen-spmi-ami');
    
            // Upload sebagai file temporary
            $result = $uploadService->upload($request->file('file'), null, true);
    
            if ($result['success']) {
                // Simpan ke session untuk digunakan nanti
                session(['temp_file' => $result['filename']]);
    
                return response()->json([
                    'success' => true,
                    'filename' => $result['filename'],
                    'file_url' => $result['file_url']
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'kategori_dokumen' => 'required|string|max:255',
                'nama_dokumen' => 'required|string|max:255',
                'temp_file' => 'required|string',
                'is_active' => 'boolean'
            ]);
    
            // Pindahkan file dari temporary ke permanent
            $userId = Auth::id();
            $tempPath = "temp/dokumen-spmi-ami/{$userId}/" . session('temp_file');
            $permanentPath = 'dokumen-spmi-ami/' . session('temp_file');
    
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->move($tempPath, $permanentPath);
                
                // Hapus folder temporary milik user ini
                Storage::disk('public')->deleteDirectory("temp/dokumen-spmi-ami/{$userId}");
                
                // Hapus session
                session()->forget('temp_file');
            } else {
                throw new \Exception('File temporary tidak ditemukan.');
            }
    
            $dokumen = DokumenSPMIAMI::create([
                'kategori_dokumen' => $request->kategori_dokumen,
                'nama_dokumen' => $request->nama_dokumen,
                'file_path' => $permanentPath,
                'is_active' => $request->has('is_active')
            ]);
    
            // Log aktivitas
            ActivityLogService::log(
                'created',
                'dokumen_spmi_ami',
                'Created new dokumen: ' . $dokumen->nama_dokumen,
                $dokumen,
                null,
                $dokumen->fresh()->toArray()
            );
    
            return redirect()->route('dokumen-spmi-ami.index')
                ->with('success', 'Dokumen berhasil ditambahkan');
        } catch (\Exception $e) {
            // Hapus folder temporary milik user ini jika terjadi error
            $userId = Auth::id();
            Storage::disk('public')->deleteDirectory("temp/dokumen-spmi-ami/{$userId}");
            session()->forget('temp_file');
    
            return back()->withInput()
                ->with('error', 'Dokumen gagal ditambahkan: ' . $e->getMessage());
        }
    }

    public function edit(DokumenSPMIAMI $dokumenSPMIAMI)
    {
        $userRoles = Auth::user()->roles->pluck('id')->toArray();
        $kategoriList = KategoriDokumen::whereHas('roles', function ($query) use ($userRoles) {
            $query->whereIn('roles.id', $userRoles);
        })->get();

        return view('dokumen-spmi-ami.edit', compact('dokumenSPMIAMI', 'kategoriList'));
    }

    public function update(Request $request, DokumenSPMIAMI $dokumenSPMIAMI)
    {
        try {
            $request->validate([
                'kategori_dokumen' => 'required|string|max:255',
                'nama_dokumen' => 'required|string|max:255',
                'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
                'is_active' => 'boolean'
            ]);

            $oldData = $dokumenSPMIAMI->toArray();

            // Upload file baru jika ada
            if ($request->hasFile('file')) {
                Storage::disk('public')->delete($dokumenSPMIAMI->file_path);
                $file = $request->file('file');
                $path = $file->store('dokumen-spmi-ami', 'public');
            }

            $dokumenSPMIAMI->update([
                'kategori_dokumen' => $request->kategori_dokumen,
                'nama_dokumen' => $request->nama_dokumen,
                'file_path' => $request->hasFile('file') ? $path : $dokumenSPMIAMI->file_path,
                'is_active' => $request->has('is_active')
            ]);

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'dokumen_spmi_ami',
                'Updated dokumen: ' . $dokumenSPMIAMI->nama_dokumen,
                $dokumenSPMIAMI,
                $oldData,
                $dokumenSPMIAMI->fresh()->toArray()
            );

            return redirect()->route('dokumen-spmi-ami.index')
                ->with('success', 'Dokumen berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Dokumen gagal diperbarui: ' . $e->getMessage());
        }
    }

    public function destroy(DokumenSPMIAMI $dokumenSPMIAMI)
    {
        try {
            $oldData = $dokumenSPMIAMI->toArray();

            Storage::disk('public')->delete($dokumenSPMIAMI->file_path);
            $dokumenSPMIAMI->delete();

            // Log aktivitas
            ActivityLogService::log(
                'deleted',
                'dokumen_spmi_ami',
                'Deleted dokumen: ' . $dokumenSPMIAMI->nama_dokumen,
                $dokumenSPMIAMI,
                $oldData,
                null
            );

            return redirect()->route('dokumen-spmi-ami.index')
                ->with('success', 'Dokumen berhasil dihapus');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Dokumen gagal dihapus: ' . $e->getMessage());
        }
    }

    public function cleanupTemp()
    {
        try {
            $userId = Auth::id();
            Storage::disk('public')->deleteDirectory("temp/dokumen-spmi-ami/{$userId}");
            session()->forget('temp_file');
            
            return response()->json(['success' => true, 'message' => 'Cleanup successful']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
