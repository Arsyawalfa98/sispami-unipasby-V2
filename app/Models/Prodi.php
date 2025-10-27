<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Prodi
 *
 * Ini adalah lightweight model untuk menyimpan data prodi di pivot table.
 * Data master prodi tetap ada di sistem mitra, ini hanya untuk caching dan relationship.
 */
class Prodi extends Model
{
    protected $table = 'user_prodi';

    protected $fillable = [
        'user_id',
        'kode_prodi',
        'nama_prodi',
        'kode_fakultas',
        'nama_fakultas',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Relationship: Prodi belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get default prodi for a user
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get full prodi name with code
     */
    public function getFullNameAttribute()
    {
        return "{$this->kode_prodi} - {$this->nama_prodi}";
    }
}
