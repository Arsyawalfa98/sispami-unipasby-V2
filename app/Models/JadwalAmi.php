<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use \App\Models\User;

class JadwalAmi extends Model
{
    protected $table = 'jadwal_ami';

    protected $fillable = [
        'prodi',
        'fakultas',
        'standar_akreditasi',
        'periode',
        'tanggal_mulai',
        'tanggal_selesai',
        // TAMBAHAN BARU untuk upload schedule
        'upload_mulai',
        'upload_selesai',
        'upload_enabled',
        'upload_keterangan'
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime:Y-m-d H:i:s',
        'tanggal_selesai' => 'datetime:Y-m-d H:i:s',
        // TAMBAHAN BARU untuk upload schedule
        'upload_mulai' => 'datetime:Y-m-d H:i:s',
        'upload_selesai' => 'datetime:Y-m-d H:i:s',
        'upload_enabled' => 'boolean'
    ];

    // ========== EXISTING RELATIONSHIPS ==========

    /**
     * Relasi ke semua tim auditor (backward compatibility)
     */
    public function timAuditor()
    {
        return $this->belongsToMany(User::class, 'jadwal_ami_auditor', 'jadwal_ami_id', 'user_id')
            ->withPivot('role_auditor')
            ->withTimestamps()
            ->orderByPivot('role_auditor', 'asc'); // Ketua di urutan pertama
    }

    /**
     * Relasi ke ketua auditor
     */
    public function ketuaAuditor()
    {
        return $this->belongsToMany(User::class, 'jadwal_ami_auditor', 'jadwal_ami_id', 'user_id')
            ->wherePivot('role_auditor', 'ketua')
            ->withPivot('role_auditor')
            ->withTimestamps();
    }

    /**
     * Relasi ke anggota auditor
     */
    public function anggotaAuditor()
    {
        return $this->belongsToMany(User::class, 'jadwal_ami_auditor', 'jadwal_ami_id', 'user_id')
            ->wherePivot('role_auditor', 'anggota')
            ->withPivot('role_auditor')
            ->withTimestamps();
    }

    // ========== TAMBAHAN BARU - UPLOAD RELATIONSHIPS ==========

    /**
     * Relationship dengan auditor uploads
     */
    public function auditorUploads()
    {
        return $this->hasMany(AuditorUpload::class);
    }

    /**
     * Relationship dengan upload comments
     */
    public function uploadComments()
    {
        return $this->hasMany(AuditorUploadComment::class);
    }

    // ========== EXISTING ACCESSORS ==========

    /**
     * Accessor untuk mendapatkan ketua auditor tunggal
     */
    public function getKetuaAuditorAttribute()
    {
        return $this->ketuaAuditor()->first();
    }

    /**
     * Accessor untuk mendapatkan semua anggota auditor
     */
    public function getAnggotaAuditorListAttribute()
    {
        return $this->anggotaAuditor()->get();
    }

    // ========== EXISTING METHODS ==========

    /**
     * Method untuk check apakah user adalah ketua auditor
     */
    public function isKetuaAuditor($userId)
    {
        return $this->ketuaAuditor()->where('users.id', $userId)->exists();
    }

    /**
     * Method untuk check apakah user adalah anggota auditor
     */
    public function isAnggotaAuditor($userId)
    {
        return $this->anggotaAuditor()->where('users.id', $userId)->exists();
    }

    // ========== TAMBAHAN BARU - UPLOAD METHODS ==========

    /**
     * Check if upload period is active
     */
    public function isUploadActive()
    {
        if (!$this->upload_enabled || !$this->upload_mulai || !$this->upload_selesai) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->upload_mulai, $this->upload_selesai);
    }

    /**
     * Check if upload period has started
     */
    public function isUploadStarted()
    {
        if (!$this->upload_enabled || !$this->upload_mulai) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->upload_mulai);
    }

    /**
     * Check if upload period has ended
     */
    public function isUploadEnded()
    {
        if (!$this->upload_enabled || !$this->upload_selesai) {
            return false;
        }

        return Carbon::now()->greaterThan($this->upload_selesai);
    }

    /**
     * Get upload status text
     */
    public function getUploadStatusAttribute()
    {
        if (!$this->upload_enabled) {
            return 'Upload Tidak Diaktifkan';
        }

        if (!$this->upload_mulai || !$this->upload_selesai) {
            return 'Jadwal Upload Belum Ditetapkan';
        }

        $now = Carbon::now();

        if ($now->lessThan($this->upload_mulai)) {
            return 'Upload Belum Dimulai';
        }

        if ($now->between($this->upload_mulai, $this->upload_selesai)) {
            return 'Upload Sedang Berlangsung';
        }

        if ($now->greaterThan($this->upload_selesai)) {
            return 'Upload Telah Selesai';
        }

        return 'Status Tidak Diketahui';
    }

    /**
     * Get upload status badge class for UI (SB Admin 2 compatible)
     */
    public function getUploadStatusBadgeAttribute()
    {
        $status = $this->upload_status;

        switch ($status) {
            case 'Upload Sedang Berlangsung':
                return 'badge-success';
            case 'Upload Belum Dimulai':
                return 'badge-warning';
            case 'Upload Telah Selesai':
                return 'badge-info';
            case 'Upload Tidak Diaktifkan':
            case 'Jadwal Upload Belum Ditetapkan':
                return 'badge-secondary';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Check if user is assigned as auditor in this schedule
     */
    public function isUserAssignedAsAuditor($userId)
    {
        return $this->timAuditor()->where('user_id', $userId)->exists();
    }

    /**
     * Get count of uploaded files for this schedule
     */
    public function getUploadedFilesCountAttribute()
    {
        return $this->auditorUploads()->count();
    }

    /**
     * Check if schedule has any uploaded files
     */
    public function hasUploadedFiles()
    {
        return $this->auditorUploads()->exists();
    }

    /**
     * Get latest upload comment
     */
    public function getLatestUploadCommentAttribute()
    {
        return $this->uploadComments()->latest()->first();
    }

    public function canUserUpload($userId)
    {
        $user = User::find($userId);
        
        if (!$user) return false;
        
        // Super Admin bisa upload kapan saja (hanya cek upload enabled)
        if ($user->hasActiveRole('Super Admin')) {
            return $this->upload_enabled;
        }
        
        // Admin LPM bisa upload dalam periode aktif
        if ($user->hasActiveRole('Admin LPM')) {
            return $this->upload_enabled && $this->isUploadActive();
        }
        
        // Auditor sesuai aturan asli (assigned + periode aktif)
        if ($user->hasActiveRole('Auditor')) {
            return $this->isUserAssignedAsAuditor($userId) && $this->isUploadActive();
        }
        
        // Role lain tidak bisa upload (tapi bisa view jika ada permission)
        return false;
    }
}
