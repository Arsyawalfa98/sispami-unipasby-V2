<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogService;

class FileUploadService
{
    protected $allowedMimeTypes = [];
    protected $allowedExtensions = [];
    protected $maxFileSize = 0;
    protected $storagePath = '';
    protected $userId;
    protected $dangerousPatterns = [
        '<?php',
        '<?=',
        '<script',
        'eval(',
        'exec(',
        'system(',
        'shell_exec(',
        'passthru(',
        'base64_decode(',
        '__halt_compiler',
        'proc_open',
        'popen'
    ];

    public function __construct()
    {
        $this->userId = Auth::id();
    }

    // Metode setter tetap sama
    public function setAllowedMimeTypes(array $mimeTypes)
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    public function setAllowedExtensions(array $extensions)
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    public function setMaxFileSize(int $size)
    {
        $this->maxFileSize = $size;
        return $this;
    }

    public function setStoragePath(string $path)
    {
        $this->storagePath = $path;
        return $this;
    }

    // Metode validasi tetap sama
    protected function validateFileType(UploadedFile $file): bool
    {
        return empty($this->allowedMimeTypes) ||
            in_array($file->getMimeType(), $this->allowedMimeTypes);
    }

    protected function validateFileExtension(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return empty($this->allowedExtensions) ||
            in_array($extension, $this->allowedExtensions);
    }

    protected function validateFileContent(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getRealPath());
        foreach ($this->dangerousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return false;
            }
        }
        return true;
    }

    protected function getTempPath()
    {
        return "temp/{$this->storagePath}/" . Auth::id();
    }

    protected function logSecurityIssue(string $message, UploadedFile $file): void
    {
        try {
            $threatData = [
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'error_message' => $message,
                'detected_at' => now()->toDateTimeString()
            ];

            ActivityLogService::log(
                'security_warning',
                'file_upload',
                $message,
                null,
                $threatData,
                null
            );

            Log::warning('Security threat detected', $threatData);
        } catch (\Exception $e) {
            Log::error('Failed to log security issue', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
        }
    }

    // Metode upload yang diperbarui
    public function upload(UploadedFile $file, ?string $oldFile = null, bool $isTemp = false)
    {
        try {
            // Validasi file
            if (!$this->validateFileType($file)) {
                $this->logSecurityIssue('Invalid file type', $file);
                return ['success' => false, 'message' => 'Invalid file type'];
            }

            if (!$this->validateFileExtension($file)) {
                $this->logSecurityIssue('Invalid file extension', $file);
                return ['success' => false, 'message' => 'Invalid file extension'];
            }

            if (!$this->validateFileContent($file)) {
                $this->logSecurityIssue('Potentially malicious content detected', $file);
                return ['success' => false, 'message' => 'Potentially malicious content detected'];
            }

            // Generate filename yang lebih sederhana
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = uniqid() . Str::random(4) . '.' . $extension;

            // Set path berdasarkan temporary atau tidak
            $path = $isTemp ? $this->getTempPath() : $this->storagePath;

            // Hapus file lama jika ada dan bukan temporary
            if (!$isTemp && $oldFile && Storage::disk('public')->exists($this->storagePath . '/' . $oldFile)) {
                Storage::disk('public')->delete($this->storagePath . '/' . $oldFile);
            }

            // Simpan file baru
            $file->storeAs('public/' . $path, $filename);

            // Log sukses
            Log::info('File uploaded successfully', [
                'filename' => $filename,
                'path' => $path,
                'is_temp' => $isTemp
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'file_url' => Storage::url($path . '/' . $filename)
            ];
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}