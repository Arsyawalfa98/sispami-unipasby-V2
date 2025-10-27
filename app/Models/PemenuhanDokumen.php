<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemenuhanDokumen extends Model
{
    protected $table = 'pemenuhan_dokumen';
    
    protected $fillable = [
        'kriteria_dokumen_id',
        'kriteria',
        'element',
        'indikator',
        'nama_dokumen',
        'tipe_dokumen',
        'keterangan',
        'file',
        'periode',
        'tambahan_informasi',
        'prodi',
        'fakultas'
    ];

    public function kriteriaDokumen()
    {
        return $this->belongsTo(KriteriaDokumen::class);
    }

    // Tambahkan relasi ke TipeDokumen
    public function tipeDokumen()
    {
        return $this->belongsTo(TipeDokumen::class, 'tipe_dokumen', 'id');
    }
}