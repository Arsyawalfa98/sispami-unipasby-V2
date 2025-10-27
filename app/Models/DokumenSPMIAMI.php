<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenSPMIAMI extends Model
{
    protected $table = 'dokumen_spmi_ami';  // Nama tabel di database
    
    protected $fillable = [
        'kategori_dokumen',
        'nama_dokumen',
        'file_path',
        'is_active'
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriDokumen::class, 'kategori_dokumen', 'nama_kategori');
    }
}