<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalAmi;
use App\Models\KriteriaDokumen;
use App\Models\LembagaAkreditasiDetail;

class CheckDataConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:data-consistency {--fix : Suggest fixes for found issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check data consistency between Jadwal AMI and Kriteria Dokumen';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('  DATA CONSISTENCY CHECKER - PEMENUHAN DOKUMEN');
        $this->info('===========================================');
        $this->newLine();

        $issues = [];

        // 1. Check Jadwal AMI without matching Kriteria Dokumen
        $this->info('1. Checking Jadwal AMI without matching Kriteria Dokumen...');
        $jadwalWithoutKriteria = $this->checkJadwalWithoutKriteria();

        if ($jadwalWithoutKriteria->isNotEmpty()) {
            $this->warn("   Found {$jadwalWithoutKriteria->count()} jadwal without matching kriteria:");

            foreach ($jadwalWithoutKriteria as $jadwal) {
                $issues[] = [
                    'type' => 'Missing Kriteria',
                    'jadwal_id' => $jadwal->id,
                    'prodi' => $jadwal->prodi,
                    'standar' => $jadwal->standar_akreditasi,
                    'reason' => $jadwal->missing_reason
                ];

                $this->line("   - ID: {$jadwal->id} | Prodi: {$jadwal->prodi}");
                $this->line("     Standar: {$jadwal->standar_akreditasi} | Periode: {$jadwal->periode}");
                $this->line("     Reason: {$jadwal->missing_reason}");
                $this->newLine();
            }
        } else {
            $this->info('   ✓ All Jadwal AMI have matching Kriteria Dokumen');
        }

        $this->newLine();

        // 2. Check prodi in Jadwal AMI not registered in lembaga_akreditasi_detail
        $this->info('2. Checking prodi in Jadwal AMI not registered in lembaga_akreditasi_detail...');
        $unregisteredProdi = $this->checkUnregisteredProdi();

        if ($unregisteredProdi->isNotEmpty()) {
            $this->warn("   Found {$unregisteredProdi->count()} prodi not registered:");

            foreach ($unregisteredProdi as $prodi) {
                $issues[] = [
                    'type' => 'Unregistered Prodi',
                    'prodi' => $prodi->prodi,
                    'fakultas' => $prodi->fakultas,
                    'standar' => $prodi->standar_akreditasi,
                    'jadwal_count' => $prodi->jadwal_count
                ];

                $this->line("   - Prodi: {$prodi->prodi}");
                $this->line("     Fakultas: {$prodi->fakultas}");
                $this->line("     Standar Akreditasi: {$prodi->standar_akreditasi}");
                $this->line("     Used in {$prodi->jadwal_count} jadwal(s)");
                $this->newLine();
            }
        } else {
            $this->info('   ✓ All prodi in Jadwal AMI are registered');
        }

        $this->newLine();

        // 3. Check kriteria dokumen without any prodi registration
        $this->info('3. Checking Kriteria Dokumen without prodi registration...');
        $kriteriaWithoutProdi = $this->checkKriteriaWithoutProdi();

        if ($kriteriaWithoutProdi->isNotEmpty()) {
            $this->warn("   Found {$kriteriaWithoutProdi->count()} kriteria without prodi:");

            foreach ($kriteriaWithoutProdi as $kriteria) {
                $issues[] = [
                    'type' => 'Kriteria Without Prodi',
                    'lembaga' => $kriteria->lembaga_nama,
                    'jenjang' => $kriteria->jenjang_nama,
                    'periode' => $kriteria->periode_atau_tahun
                ];

                $this->line("   - Lembaga: {$kriteria->lembaga_nama}");
                $this->line("     Jenjang: {$kriteria->jenjang_nama}");
                $this->line("     Periode: {$kriteria->periode_atau_tahun}");
                $this->newLine();
            }
        } else {
            $this->info('   ✓ All Kriteria Dokumen have prodi registration');
        }

        $this->newLine();
        $this->info('===========================================');
        $this->info('SUMMARY');
        $this->info('===========================================');

        if (empty($issues)) {
            $this->info('✓ No data consistency issues found!');
        } else {
            $this->error("✗ Found " . count($issues) . " consistency issue(s)");
            $this->newLine();

            if ($this->option('fix')) {
                $this->info('SUGGESTED FIXES:');
                $this->newLine();

                // Group by type
                $byType = collect($issues)->groupBy('type');

                if ($byType->has('Unregistered Prodi')) {
                    $this->line('For Unregistered Prodi issues:');
                    $this->line('1. Go to Master Data > Lembaga Akreditasi Detail');
                    $this->line('2. Add the following prodi:');
                    foreach ($byType->get('Unregistered Prodi') as $issue) {
                        $this->line("   - {$issue['prodi']} under {$issue['standar']}");
                    }
                    $this->newLine();
                }

                if ($byType->has('Missing Kriteria')) {
                    $this->line('For Missing Kriteria issues:');
                    $this->line('1. Go to Master Data > Kriteria Dokumen');
                    $this->line('2. Create kriteria for:');
                    foreach ($byType->get('Missing Kriteria') as $issue) {
                        $this->line("   - {$issue['standar']} | Prodi: {$issue['prodi']}");
                    }
                    $this->newLine();
                }

                if ($byType->has('Kriteria Without Prodi')) {
                    $this->line('For Kriteria Without Prodi issues:');
                    $this->line('Either:');
                    $this->line('1. Add prodi registration to these kriteria, OR');
                    $this->line('2. Delete unused kriteria if not needed');
                    $this->newLine();
                }
            }
        }

        return empty($issues) ? 0 : 1;
    }

    /**
     * Check jadwal without matching kriteria
     */
    private function checkJadwalWithoutKriteria()
    {
        $allJadwal = JadwalAmi::all();
        $jadwalWithoutKriteria = collect();

        foreach ($allJadwal as $jadwal) {
            // Detect jenjang from prodi
            $jenjangList = $this->detectJenjangFromProdi($jadwal->prodi);

            if (empty($jenjangList)) {
                $jadwal->missing_reason = "Cannot detect jenjang from prodi name";
                $jadwalWithoutKriteria->push($jadwal);
                continue;
            }

            // Check if kriteria exists
            $hasMatch = false;
            foreach ($jenjangList as $jenjangName) {
                $matchingKriteria = KriteriaDokumen::whereHas('lembagaAkreditasi', function($q) use ($jadwal) {
                    $q->where('nama', $jadwal->standar_akreditasi);
                })
                ->whereHas('jenjang', function($q) use ($jenjangName) {
                    $q->where('nama', $jenjangName);
                })
                ->whereHas('lembagaAkreditasi.lembagaAkreditasiDetail', function($q) use ($jadwal) {
                    $q->where('prodi', $jadwal->prodi);
                })
                ->exists();

                if ($matchingKriteria) {
                    $hasMatch = true;
                    break;
                }
            }

            if (!$hasMatch) {
                // Check specific reason
                $kriteriaExists = KriteriaDokumen::whereHas('lembagaAkreditasi', function($q) use ($jadwal) {
                    $q->where('nama', $jadwal->standar_akreditasi);
                })
                ->whereHas('jenjang', function($q) use ($jenjangList) {
                    $q->whereIn('nama', $jenjangList);
                })
                ->exists();

                if (!$kriteriaExists) {
                    $jadwal->missing_reason = "Kriteria for {$jadwal->standar_akreditasi} with jenjang " . implode('/', $jenjangList) . " not found";
                } else {
                    $jadwal->missing_reason = "Prodi '{$jadwal->prodi}' not registered in lembaga_akreditasi_detail for {$jadwal->standar_akreditasi}";
                }

                $jadwalWithoutKriteria->push($jadwal);
            }
        }

        return $jadwalWithoutKriteria;
    }

    /**
     * Check unregistered prodi
     */
    private function checkUnregisteredProdi()
    {
        $allJadwal = JadwalAmi::select('prodi', 'fakultas', 'standar_akreditasi')
            ->selectRaw('COUNT(*) as jadwal_count')
            ->groupBy('prodi', 'fakultas', 'standar_akreditasi')
            ->get();

        $unregistered = collect();

        foreach ($allJadwal as $jadwal) {
            $isRegistered = LembagaAkreditasiDetail::whereHas('lembagaAkreditasi', function($q) use ($jadwal) {
                $q->where('nama', $jadwal->standar_akreditasi);
            })
            ->where('prodi', $jadwal->prodi)
            ->exists();

            if (!$isRegistered) {
                $unregistered->push($jadwal);
            }
        }

        return $unregistered;
    }

    /**
     * Check kriteria without prodi registration
     */
    private function checkKriteriaWithoutProdi()
    {
        $allKriteria = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang'])
            ->select('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun')
            ->groupBy('lembaga_akreditasi_id', 'jenjang_id', 'periode_atau_tahun')
            ->get();

        $withoutProdi = collect();

        foreach ($allKriteria as $kriteria) {
            $hasProdi = $kriteria->lembagaAkreditasi
                && $kriteria->lembagaAkreditasi->lembagaAkreditasiDetail
                && $kriteria->lembagaAkreditasi->lembagaAkreditasiDetail->isNotEmpty();

            if (!$hasProdi) {
                $kriteria->lembaga_nama = $kriteria->lembagaAkreditasi->nama ?? 'N/A';
                $kriteria->jenjang_nama = $kriteria->jenjang->nama ?? 'N/A';
                $withoutProdi->push($kriteria);
            }
        }

        return $withoutProdi;
    }

    /**
     * Detect jenjang from prodi string
     */
    private function detectJenjangFromProdi($prodiString)
    {
        if (empty($prodiString)) {
            return [];
        }

        $prodiLower = strtolower($prodiString);

        // Check if profesi/PPG prodi
        $isProfesi = strpos($prodiLower, 'profesi') !== false
                  || strpos($prodiLower, 'ppg') !== false
                  || strpos($prodiLower, 'program profesi') !== false
                  || strpos($prodiLower, 'pp') !== false;

        if ($isProfesi) {
            // Return all profesi jenjang variants
            return ['PROFESI', 'PPG', 'Program Profesi', 'PP'];
        }

        // Detect standard jenjang
        if (strpos($prodiLower, 's3') !== false || strpos($prodiLower, 'doktor') !== false) {
            return ['S3'];
        }
        if (strpos($prodiLower, 's2') !== false || strpos($prodiLower, 'magister') !== false) {
            return ['S2'];
        }
        if (strpos($prodiLower, 's1') !== false || strpos($prodiLower, 'sarjana') !== false) {
            return ['S1'];
        }
        if (strpos($prodiLower, 'd4') !== false || strpos($prodiLower, 'sarjana terapan') !== false) {
            return ['D4'];
        }
        if (strpos($prodiLower, 'd3') !== false || strpos($prodiLower, 'diploma') !== false) {
            return ['D3'];
        }

        return [];
    }
}
