<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AuditorUpload extends Model
{
    protected $table = 'auditor_uploads';

    protected $fillable = [
        'jadwal_ami_id',
        'auditor_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'file_type',
        'keterangan',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer'
    ];

    /**
     * Relationship dengan JadwalAmi
     */
    public function jadwalAmi()
    {
        return $this->belongsTo(JadwalAmi::class);
    }

    /**
     * Relationship dengan User (Auditor)
     */
    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get file download URL
     */
    public function getDownloadUrlAttribute()
    {
        return route('auditor-upload.download', $this->id);
    }

    /**
     * Get file view URL (for PDF)
     */
    public function getViewUrlAttribute()
    {
        if ($this->file_type === 'pdf') {
            return route('auditor-upload.view', $this->id);
        }
        return $this->download_url;
    }

    /**
     * Check if file can be viewed in browser
     */
    public function isViewableAttribute()
    {
        return $this->file_type === 'pdf';
    }

    /**
     * Get file icon class for SB Admin 2
     */
    public function getFileIconAttribute()
    {
        switch ($this->file_type) {
            case 'pdf':
                return 'fas fa-file-pdf text-danger';
            case 'doc':
            case 'docx':
                return 'fas fa-file-word text-primary';
            default:
                return 'fas fa-file text-secondary';
        }
    }

    /**
     * Generate unique stored name for security
     */
    public static function generateStoredName($originalName, $auditorId)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $timestamp = now()->format('YmdHis');
        $random = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8);
        
        return "{$timestamp}_{$random}_{$auditorId}.{$extension}";
    }

    /**
     * Get full file path for storage
     */
    public function getFullPathAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if file exists in storage
     */
    public function fileExists()
    {
        return file_exists($this->full_path);
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($upload) {
            if ($upload->fileExists()) {
                unlink($upload->full_path);
            }
        });
    }

    /**
     * Scope untuk filter berdasarkan jadwal AMI
     */
    public function scopeForJadwalAmi($query, $jadwalAmiId)
    {
        return $query->where('jadwal_ami_id', $jadwalAmiId);
    }

    /**
     * Scope untuk filter berdasarkan auditor
     */
    public function scopeByAuditor($query, $auditorId)
    {
        return $query->where('auditor_id', $auditorId);
    }

    /**
     * Scope untuk ordering berdasarkan upload terbaru
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('uploaded_at', 'desc');
    }
}