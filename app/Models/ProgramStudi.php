<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    protected $table = 'program_studi';
    
    protected $fillable = [
        'nama_prodi',
        'jenjang',
        'fakultas',
        'status_akreditasi',
        'tanggal_kadarluarsa',
        'bukti'
    ];

    protected $casts = [
        'tanggal_kadarluarsa' => 'date'
    ];
}
