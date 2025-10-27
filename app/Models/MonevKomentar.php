<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonevKomentar extends Model
{
    protected $table = 'monev_komentar'; // ← UPDATED: Nama tabel baru
    
    protected $fillable = [
        'kriteria_dokumen_id',
        'prodi',
        'status_temuan', // ← NEW FIELD
        'komentar_element',
        'user_id'
    ];

    /**
     * Relationship dengan KriteriaDokumen
     */
    public function kriteriaDokumen(): BelongsTo
    {
        return $this->belongsTo(KriteriaDokumen::class);
    }

    /**
     * Relationship dengan User (yang membuat komentar)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ⭐ UPDATED: Scope untuk filter berdasarkan kriteria, prodi DAN status_temuan
     */
    public function scopeForKriteriaProdi($query, $kriteriaId, $prodi, $statusTemuan = 'KETIDAKSESUAIAN')
    {
        return $query->where('kriteria_dokumen_id', $kriteriaId)
                     ->where('prodi', $prodi)
                     ->where('status_temuan', $statusTemuan);
    }

    /**
     * Scope untuk ordering berdasarkan terbaru
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    
    /**
     * ⭐ NEW: Scope untuk filter berdasarkan status temuan
     */
    public function scopeByStatusTemuan($query, $statusTemuan)
    {
        return $query->where('status_temuan', $statusTemuan);
    }
}