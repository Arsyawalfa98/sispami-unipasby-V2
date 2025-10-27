<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriDokumen extends Model
{
    protected $table = 'kategori_dokumen';
    protected $fillable = ['nama_kategori'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'kategori_dokumen_role');
    }
}