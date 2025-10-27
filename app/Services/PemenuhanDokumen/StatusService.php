<?php
namespace App\Services\PemenuhanDokumen;

use Carbon\Carbon;

class StatusService
{
    public function getStatus($jadwal)
    {
        if (!$jadwal) {
            return 'Belum ada jadwal';
        }

        $now = now();
        $tanggalMulai = Carbon::parse($jadwal['tanggal_mulai']);
        $tanggalSelesai = Carbon::parse($jadwal['tanggal_selesai']);

        if ($now->gt($tanggalSelesai)) {
            return 'Selesai AMI';
        }

        if ($now->between($tanggalMulai, $tanggalSelesai)) {
            return 'Sedang Berlangsung';
        }

        return 'Belum Dimulai';
    }
}