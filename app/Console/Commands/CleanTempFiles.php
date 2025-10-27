<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanTempFiles extends Command
{
    protected $signature = 'temp:clean';
    protected $description = 'Clean temporary files based on specified time limits';

    /**
     * Daftar folder dan batas waktu pembersihan
     */
    protected $folders = [
        'dokumen-spmi-ami' => 3600,      // 1 jam dalam detik
        'bukti_akreditasi' => 300,        // 5 menit dalam detik
        'pemenuhan_dokumen' => 300
    ];

    public function handle()
    {
        foreach ($this->folders as $folder => $timeLimit) {
            $this->cleanFolder($folder, $timeLimit);
        }
    }

    /**
     * Membersihkan folder berdasarkan batas waktu
     */
    protected function cleanFolder($folder, $timeLimit)
    {
        // Kembali ke path yang benar (yang lama)
        $tempPath = storage_path("app/public/temp/{$folder}");
        
        // Debug log untuk memastikan path yang dicek
        Log::info("Checking folder path", [
            'path' => $tempPath,
            'exists' => File::exists($tempPath)
        ]);
    
        if (!File::exists($tempPath)) {
            $this->warn("Folder {$folder} tidak ditemukan di: {$tempPath}");
            Log::warning("Folder not found", ['path' => $tempPath]);
            return;
        }
    
        try {
            $count = 0;
            $now = time();
            
            // Debug: List semua file yang ditemukan
            $files = File::allFiles($tempPath);
            Log::info("Files found in directory", [
                'folder' => $folder,
                'count' => count($files),
                'files' => collect($files)->map(fn($file) => $file->getFilename())->toArray()
            ]);
            
            foreach ($files as $file) {
                try {
                    $filePath = $file->getRealPath();
                    $fileName = $file->getFilename();
                    $fileAge = $now - File::lastModified($file);
                    
                    // Debug log untuk setiap file
                    Log::info("Processing file", [
                        'file' => $fileName,
                        'age' => $fileAge,
                        'timeLimit' => $timeLimit,
                        'shouldDelete' => $fileAge > $timeLimit
                    ]);
    
                    if ($fileAge > $timeLimit) {
                        if (File::delete($filePath)) {
                            $count++;
                            Log::info("File deleted successfully", [
                                'file' => $fileName,
                                'age' => $fileAge,
                                'timeLimit' => $timeLimit
                            ]);
                        }
                    } else {
                        Log::info("File not deleted - age not exceeded limit", [
                            'file' => $fileName,
                            'age' => $fileAge,
                            'timeLimit' => $timeLimit
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing file", [
                        'file' => $fileName ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
    
            // Bersihkan folder kosong
            $directories = File::directories($tempPath);
            foreach ($directories as $directory) {
                if (count(File::allFiles($directory)) === 0) {
                    if (File::deleteDirectory($directory)) {
                        $count++;
                        Log::info("Empty directory deleted", [
                            'directory' => basename($directory)
                        ]);
                    }
                }
            }
    
            // Output hasil yang lebih informatif
            if ($count > 0) {
                $this->info("Berhasil membersihkan {$count} item dari folder {$folder}");
            } else {
                $this->info("Tidak ada item yang perlu dibersihkan di folder {$folder} (File belum melewati batas waktu)");
            }
    
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("Error dalam proses pembersihan", [
                'folder' => $folder,
                'error' => $e->getMessage()
            ]);
        }
    }
}