<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonevGlobalKomentar extends Model
{
    protected $table = 'monev_global_komentar'; // ← UPDATED: Nama tabel baru
    
    protected $fillable = [
        'lembaga_akreditasi_id',
        'jenjang_id',
        'prodi',
        'periode_atau_tahun',
        'status_temuan', // ← NEW FIELD
        'komentar_global',
        'admin_id'
    ];

    /**
     * Relationship dengan LembagaAkreditasi
     */
    public function lembagaAkreditasi(): BelongsTo
    {
        return $this->belongsTo(LembagaAkreditasi::class);
    }

    /**
     * Relationship dengan Jenjang
     */
    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    /**
     * Relationship dengan User (Admin yang membuat komentar)
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * ⭐ UPDATED: Scope untuk filter berdasarkan group DAN status_temuan
     */
    public function scopeForGroup($query, $lembagaId, $jenjangId, $prodi, $periode, $statusTemuan = 'KETIDAKSESUAIAN')
    {
        return $query->where('lembaga_akreditasi_id', $lembagaId)
                     ->where('jenjang_id', $jenjangId)
                     ->where('prodi', $prodi)
                     ->where('periode_atau_tahun', $periode)
                     ->where('status_temuan', $statusTemuan);
    }

    /**
     * Scope untuk ordering berdasarkan terbaru
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    
    public function scopeByStatusTemuan($query, $statusTemuan)
    {
        return $query->where('status_temuan', $statusTemuan);
    }
}