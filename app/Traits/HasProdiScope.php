<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasProdiScope
 *
 * Trait ini menyediakan query scope untuk filter data berdasarkan active prodi.
 * Gunakan trait ini di model yang memiliki kolom 'prodi' atau 'kode_prodi'.
 *
 * Usage:
 * class PemenuhanDokumen extends Model {
 *     use HasProdiScope;
 * }
 *
 * // Automatic filtering
 * $dokumen = PemenuhanDokumen::forActiveProdi()->get();
 *
 * // Or specific prodi
 * $dokumen = PemenuhanDokumen::forProdi('PENJAS')->get();
 */
trait HasProdiScope
{
    /**
     * Scope query untuk filter berdasarkan active prodi dari session
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeForActiveProdi(Builder $query)
    {
        $activeProdi = getActiveProdi();

        if ($activeProdi) {
            return $this->scopeForProdi($query, $activeProdi);
        }

        return $query;
    }

    /**
     * Scope query untuk filter berdasarkan prodi tertentu
     *
     * @param Builder $query
     * @param string $kodeProdi
     * @return Builder
     */
    public function scopeForProdi(Builder $query, $kodeProdi)
    {
        // Deteksi nama kolom yang digunakan
        $columnName = $this->getProdiColumnName();

        return $query->where($columnName, $kodeProdi);
    }

    /**
     * Get nama kolom prodi yang digunakan di table
     * Override method ini jika nama kolom berbeda
     *
     * @return string
     */
    protected function getProdiColumnName()
    {
        // Cek apakah ada property $prodiColumn yang di-define di model
        if (property_exists($this, 'prodiColumn')) {
            return $this->prodiColumn;
        }

        // Auto-detect: cek kolom yang ada
        $tableName = $this->getTable();
        $schema = $this->getConnection()->getSchemaBuilder();

        if ($schema->hasColumn($tableName, 'kode_prodi')) {
            return 'kode_prodi';
        }

        if ($schema->hasColumn($tableName, 'prodi')) {
            return 'prodi';
        }

        // Default fallback
        return 'prodi';
    }

    /**
     * Scope untuk mengambil data dari multiple prodi
     *
     * @param Builder $query
     * @param array $kodeProdis
     * @return Builder
     */
    public function scopeForProdis(Builder $query, array $kodeProdis)
    {
        $columnName = $this->getProdiColumnName();
        return $query->whereIn($columnName, $kodeProdis);
    }

    /**
     * Scope untuk mengambil semua prodi yang accessible oleh current user
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeForUserProdis(Builder $query)
    {
        $userProdis = getAllUserProdi()->pluck('kode_prodi')->toArray();

        if (!empty($userProdis)) {
            return $this->scopeForProdis($query, $userProdis);
        }

        return $query;
    }
}
