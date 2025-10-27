<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KriteriaDokumen extends Model
{
    protected $table = 'kriteria_dokumen';
    
    protected $fillable = [
        'lembaga_akreditasi_id',
        'jenjang_id', 
        'judul_kriteria_dokumen_id',
        'periode_atau_tahun',
        'kode',
        'element',
        'indikator',
        'informasi',
        'kebutuhan_dokumen',
        'bobot'
    ];

    public function lembagaAkreditasi()
    {
        return $this->belongsTo(LembagaAkreditasi::class);
    }

    public function jenjang() 
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function judulKriteriaDokumen()
    {
        return $this->belongsTo(JudulKriteriaDokumen::class, 'judul_kriteria_dokumen_id', 'id');
    }
    
    public function kebutuhanDokumen()
    {
        return $this->hasMany(KelolaKebutuhanKriteriaDokumen::class);
    }
    public function kelolaKebutuhanKriteriaDokumen()
    {
        return $this->hasMany(KelolaKebutuhanKriteriaDokumen::class, 'kriteria_dokumen_id');
    }

    public function tipeDokumen()
    {
        return $this->belongsTo(TipeDokumen::class, 'tipe_dokumen_id');
    }

    public function penilaianKriteria()
    {
        return $this->hasMany(PenilaianKriteria::class);
    }
}