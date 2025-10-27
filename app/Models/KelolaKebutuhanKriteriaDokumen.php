<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelolaKebutuhanKriteriaDokumen extends Model
{
    protected $table = 'kelola_kebutuhan_kriteria_dokumen';
    
    protected $fillable = [
        'kriteria_dokumen_id',
        'nama_dokumen',
        'tipe_dokumen',
        'keterangan',
        'bobot'
    ];

    // Tambahkan appends untuk accessor
    protected $appends = ['tipe_dokumen_nama'];

    public function kriteriaDokumen()
    {
        return $this->belongsTo(KriteriaDokumen::class);
    }

    // Relasi ke tipe dokumen
    public function tipeDokumen()
    {
        return $this->belongsTo(TipeDokumen::class, 'tipe_dokumen', 'id');
    }
    
    // Accessor untuk mendapatkan nama tipe dokumen
    public function getTipeDokumenNamaAttribute()
    {
        // Jika relasi tipeDokumen diload dan ada namanya
        if ($this->tipeDokumen && $this->tipeDokumen->nama) {
            return $this->tipeDokumen->nama;
        }
        
        // Jika relasi tidak diload tetapi nilai tipe_dokumen ada
        if ($this->tipe_dokumen) {
            // Ambil langsung dari database
            $tipeDokumen = TipeDokumen::find($this->tipe_dokumen);
            if ($tipeDokumen) {
                return $tipeDokumen->nama;
            }
        }
        
        // Default jika tidak ada
        return '(Belum Di Setting Di Kriteria Dokumen)';
    }
}