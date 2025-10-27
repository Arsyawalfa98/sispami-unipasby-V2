<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LembagaAkreditasiDetail extends Model  
{
    protected $table = 'lembaga_akreditasi_detail';
    
    protected $fillable = [
        'lembaga_akreditasi_id',
        'prodi',
        'fakultas'
    ];

    
    public function lembagaAkreditasi()
    {
        return $this->belongsTo(LembagaAkreditasi::class);
    }
}