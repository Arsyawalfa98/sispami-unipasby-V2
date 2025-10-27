<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LembagaAkreditasi extends Model
{
    protected $table = 'lembaga_akreditasi';
    
    protected $fillable = [
        'nama',
        'tahun'
    ];

    protected $casts = [
        'tahun' => 'integer'
    ];

     // Tambahkan relasi
     public function lembagaAkreditasiDetail()
     {
         return $this->hasMany(LembagaAkreditasiDetail::class, 'lembaga_akreditasi_id');
     }
     
    // Relasi dengan detail prodi
    public function details()
    {
        return $this->hasMany(LembagaAkreditasiDetail::class);
    }
}