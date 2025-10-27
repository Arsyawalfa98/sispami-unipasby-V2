<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JudulKriteriaDokumen extends Model
{
    protected $table = 'judul_kriteria_dokumen';

    protected $fillable = ['nama_kriteria_dokumen'];

    public function kriteriaDokumen()
    {
        return $this->hasMany(KriteriaDokumen::class, 'judul_kriteria_dokumen_id', 'id');
    }
}
