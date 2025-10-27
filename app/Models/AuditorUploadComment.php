<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditorUploadComment extends Model
{
    protected $table = 'auditor_upload_comments';

    protected $fillable = [
        'jadwal_ami_id',
        'admin_id',
        'komentar'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship dengan JadwalAmi
     */
    public function jadwalAmi()
    {
        return $this->belongsTo(JadwalAmi::class);
    }

    /**
     * Relationship dengan User (Admin yang memberikan komentar)
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get formatted created date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    /**
     * Get time ago format
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scope untuk filter berdasarkan jadwal AMI
     */
    public function scopeForJadwalAmi($query, $jadwalAmiId)
    {
        return $query->where('jadwal_ami_id', $jadwalAmiId);
    }

    /**
     * Scope untuk ordering berdasarkan komentar terbaru
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope untuk ordering berdasarkan komentar terlama
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Get latest comment for a specific jadwal ami
     */
    public static function getLatestForJadwal($jadwalAmiId)
    {
        return static::forJadwalAmi($jadwalAmiId)->latest()->first();
    }

    /**
     * Check if there's any comment for specific jadwal ami
     */
    public static function hasCommentForJadwal($jadwalAmiId)
    {
        return static::forJadwalAmi($jadwalAmiId)->exists();
    }
}