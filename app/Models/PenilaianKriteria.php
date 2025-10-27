<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianKriteria extends Model
{
    protected $table = 'penilaian_kriteria';
    
    // Definisi status sebagai konstanta
    const STATUS_DRAFT = 'draft';
    const STATUS_PENILAIAN = 'penilaian';
    const STATUS_DIAJUKAN = 'diajukan';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK = 'ditolak';
    const STATUS_REVISI = 'revisi';  
    
    protected $fillable = [
        'kriteria_dokumen_id',
        'prodi',
        'fakultas',
        'periode_atau_tahun',
        'status',
        'nilai',
        'sebutan',
        'bobot',
        'tertimbang',
        'nilai_auditor',
        'revisi',
        'revisi_confirmed',
        'tanggal_pemenuhan',
        'penanggung_jawab',
        'status_temuan',
        'hasil_ami',
        'output',
        'akar_penyebab_masalah',
        'tinjauan_efektivitas_koreksi',
        'kesimpulan'
    ];

    public function kriteriaDokumen()
    {
        return $this->belongsTo(KriteriaDokumen::class);
    }
    
    // Method untuk mengecek apakah semua kriteria sudah memiliki penilaian
    public static function isAllCriteriaMet($kriteriaDokumen)
    {
        foreach ($kriteriaDokumen as $items) {
            foreach ($items as $item) {
                $penilaian = self::where('kriteria_dokumen_id', $item->id)
                            ->where('prodi', request('prodi'))
                            ->first();
                            
                if (!$penilaian || is_null($penilaian->nilai)) {
                    return false;
                }
            }
        }
        return true;
    }
}