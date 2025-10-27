<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipeDokumen extends Model
{
    protected $table = 'tipe_dokumen';
    
    protected $fillable = ['nama'];
}