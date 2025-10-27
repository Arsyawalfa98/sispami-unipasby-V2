<?php

namespace App\Http\Controllers;

use App\Models\KriteriaDokumen;
use App\Models\PenilaianKriteria;
use App\Models\Siakad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogService;

class PenilaianKriteriaController extends Controller
{

    protected function isJadwalActive($prodi, $periode)
    {
        $jadwalAmi = \App\Models\JadwalAmi::where('prodi', 'like', "%{$prodi}%")
            ->whereRaw("LEFT(periode, 4) = ?", [$periode])
            ->first();

        if (!$jadwalAmi) {
            return false;
        }

        $jadwalMulai = \Carbon\Carbon::parse($jadwalAmi->tanggal_mulai);
        $jadwalSelesai = \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai);
        $now = \Carbon\Carbon::now();

        return $now->between($jadwalMulai, $jadwalSelesai);
    }


    public function index($kriteriaDokumenId, $informasi = null)
    {
        $user = Auth::user();
        $selectedProdi = request('prodi');
        $informasi = request('informasi');

        $kodeProdi = null;
        if ($selectedProdi) {
            // Find the position of the "-"
            $dashPosition = strpos($selectedProdi, '-');

            if ($dashPosition !== false) {
                // Extract the part before the "-" and trim any whitespace
                $kodeProdi = trim(substr($selectedProdi, 0, $dashPosition));
            }
        }

        // Initialize with default
        $namaKaprodi = 'Tidak Tersedia';

        // Special case for Program Profesi
        if ($selectedProdi && stripos($selectedProdi, 'Program Profesi') !== false) {
            $namaKaprodi = 'Erna Puji Astutik, S.Si., M.Pd., M.Sc.';
        } else {
            $kaprodiName = Siakad::getKaprodiByKodeUnit($kodeProdi);
            if ($kaprodiName) {
                $namaKaprodi = $kaprodiName;
            }
        }

        // Get base kriteria dokumen data
        $kriteriaDokumen = KriteriaDokumen::with(['lembagaAkreditasi', 'jenjang', 'judulKriteriaDokumen'])
            ->findOrFail($kriteriaDokumenId);

        // Get penilaian data if exists
        $penilaian = PenilaianKriteria::where('kriteria_dokumen_id', $kriteriaDokumenId)
            ->where('prodi', $selectedProdi)
            ->first();

        if (!$penilaian) {
            // Jika belum ada, buat penilaian baru (draft)
            $penilaian = new PenilaianKriteria([
                'kriteria_dokumen_id' => $kriteriaDokumenId,
                'prodi' => $selectedProdi,
                'fakultas' => $user->fakultas,
                'periode_atau_tahun' => $kriteriaDokumen->periode_atau_tahun,
                'status' => PenilaianKriteria::STATUS_DRAFT
            ]);
        }

        // Cek status jadwal AMI
        $jadwalActive = $this->isJadwalActive($selectedProdi, $kriteriaDokumen->periode_atau_tahun);

        return view('penilaian-kriteria.index', [
            'kriteriaDokumen' => $kriteriaDokumen,
            'penilaian' => $penilaian,
            'selectedProdi' => $selectedProdi,
            'jadwalActive' => $jadwalActive, // tambahkan variable baru ke view
            'informasi' => $informasi,
            'namaKaprodi' => $namaKaprodi,
        ]);
    }

    public function store(Request $request, $kriteriaDokumenId)
    {
        $request->validate([
            'nilai' => 'required|numeric|min:0|max:4',
            'bobot' => 'required|numeric|min:0',
            'revisi' => 'nullable|string',
            'tanggal_pemenuhan' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    try {
                        \Carbon\Carbon::parse($value);
                    } catch (\Exception $e) {
                        $fail('Format tanggal mulai tidak valid.');
                    }
                }
            ],
            'penanggung_jawab' => 'nullable|string',
            'status_temuan' => 'nullable|string',
            'hasil_ami' => 'nullable|string',
            'output' => 'nullable|string',
            'akar_penyebab_masalah' => 'nullable|string',
            'tinjauan_efektivitas_koreksi' => 'nullable|string',
            'kesimpulan' => 'nullable|string',
        ]);
        // dd($request->all());
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $selectedProdi = $request->prodi;

            if (!$selectedProdi) {
                throw new \Exception('Program studi harus dipilih');
            }

            $kriteriaDokumen = KriteriaDokumen::findOrFail($kriteriaDokumenId);

            // Cek jadwal AMI - Super Admin dan Admin LPM dikecualikan dari pengecekan
            if (!Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
                $isJadwalActive = $this->isJadwalActive($selectedProdi, $kriteriaDokumen->periode_atau_tahun);
                if (!$isJadwalActive) {
                    throw new \Exception('Jadwal AMI untuk periode ini telah berakhir atau belum di mulai. Tidak dapat menyimpan penilaian.');
                }
            }

            // Cari penilaian yang sudah ada atau buat baru
            $penilaian = PenilaianKriteria::firstOrNew([
                'kriteria_dokumen_id' => $kriteriaDokumenId,
                'prodi' => $selectedProdi
            ]);

            $oldData = $penilaian->exists ? $penilaian->toArray() : null;

            // Tidak ada yang boleh mengubah penilaian dengan status DISETUJUI
            if ($penilaian->exists && $penilaian->status == PenilaianKriteria::STATUS_DISETUJUI) {
                throw new \Exception('Penilaian dengan status disetujui tidak dapat diubah.');
            }

            // Cek akses berdasarkan peran dan status penilaian
            if (Auth::user()->hasActiveRole('Auditor') && !Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
                // Auditor hanya bisa mengubah penilaian dengan status DIAJUKAN
                if ($penilaian->exists && $penilaian->status != PenilaianKriteria::STATUS_DIAJUKAN) {
                    throw new \Exception('Auditor hanya dapat mengubah penilaian yang statusnya telah diajukan.');
                }
            }

            if (Auth::user()->hasActiveRole('Admin Prodi') && !Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
                // Admin bisa mengubah penilaian dengan status REVISI atau STATUS_PENILAIAN
                if (
                    $penilaian->exists &&
                    $penilaian->status != PenilaianKriteria::STATUS_REVISI &&
                    $penilaian->status != PenilaianKriteria::STATUS_PENILAIAN
                ) {
                    throw new \Exception('Admin hanya dapat mengubah penilaian yang statusnya revisi atau penilaian.');
                }
            }

            // Isi data penilaian
            $penilaian->fakultas = $user->fakultas;
            $penilaian->periode_atau_tahun = $kriteriaDokumen->periode_atau_tahun;

            // Super Admin, Admin LPM, dan Admin (dengan status REVISI atau STATUS_PENILAIAN) dapat mengubah nilai
            if (
                Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM') ||
                (Auth::user()->hasActiveRole('Admin Prodi') &&
                    ($penilaian->status == PenilaianKriteria::STATUS_REVISI ||
                        $penilaian->status == PenilaianKriteria::STATUS_PENILAIAN))
            ) {
                $penilaian->nilai = $request->nilai;
                $penilaian->bobot = $request->bobot;

                // Hitung tertimbang
                $penilaian->tertimbang = $request->nilai * $request->bobot;

                // Tentukan sebutan berdasarkan nilai
                if ($request->nilai == 4) {
                    $penilaian->sebutan = 'Sangat Baik';
                } elseif ($request->nilai >= 3 && $request->nilai < 4) {
                    $penilaian->sebutan = 'Baik';
                } elseif ($request->nilai >= 2 && $request->nilai < 3) {
                    $penilaian->sebutan = 'Cukup';
                } elseif ($request->nilai >= 1 && $request->nilai < 2) {
                    $penilaian->sebutan = 'Kurang';
                } else {
                    $penilaian->sebutan = 'Sangat Kurang';
                }
            }

            // Set nilai auditor jika user adalah auditor, Super Admin, atau Admin LPM
            if ((Auth::user()->hasActiveRole('Auditor') && $penilaian->status == PenilaianKriteria::STATUS_DIAJUKAN)
                || Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM')
            ) {
                $penilaian->nilai = $request->nilai_auditor ?? $request->nilai;
                $penilaian->revisi = $request->revisi;

                // Tangani konfirmasi revisi
                if ($request->has('revisi_confirmed')) {
                    $penilaian->revisi_confirmed = true;
                } else {
                    $penilaian->revisi_confirmed = false;
                }

                $penilaian->tanggal_pemenuhan = $request->tanggal_pemenuhan;
                $penilaian->penanggung_jawab = $request->penanggung_jawab;
                $penilaian->status_temuan = $request->status_temuan;
                $penilaian->hasil_ami = $request->hasil_ami;
                $penilaian->output = $request->output;
                $penilaian->akar_penyebab_masalah = $request->akar_penyebab_masalah;
                $penilaian->tinjauan_efektivitas_koreksi = $request->tinjauan_efektivitas_koreksi;
                $penilaian->kesimpulan = $request->kesimpulan;
            }

            // Set status penilaian untuk Super Admin, Admin LPM
            if ((!$penilaian->status || $penilaian->status == PenilaianKriteria::STATUS_DRAFT) &&
                (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM'))
            ) {
                $penilaian->status = PenilaianKriteria::STATUS_DIAJUKAN;
            }
            // dd($penilaian);
            $penilaian->save();

            DB::commit();

            // Log aktivitas
            ActivityLogService::log(
                $oldData ? 'updated' : 'created',
                'penilaian_kriteria',
                ($oldData ? 'Updated' : 'Created') . ' penilaian kriteria for ' . $kriteriaDokumen->judulKriteriaDokumen->nama_kriteria_dokumen,
                $penilaian,
                $oldData,
                $penilaian->fresh()->toArray()
            );

            return redirect()->route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                'kriteriaDokumenId' => $kriteriaDokumenId,
                'prodi' => $selectedProdi
            ])->with('success', 'Penilaian berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Store penilaian error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nilai' => 'required|numeric|min:0|max:4',
            'bobot' => 'required|numeric|min:0',
            'revisi' => 'nullable|string',
            'tanggal_pemenuhan' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    try {
                        \Carbon\Carbon::parse($value);
                    } catch (\Exception $e) {
                        $fail('Format tanggal mulai tidak valid.');
                    }
                }
            ],
            'penanggung_jawab' => 'nullable|string',
            'status_temuan' => 'nullable|string',
            'hasil_ami' => 'nullable|string',
            'output' => 'nullable|string',
            'akar_penyebab_masalah' => 'nullable|string',
            'tinjauan_efektivitas_koreksi' => 'nullable|string',
            'kesimpulan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $penilaian = PenilaianKriteria::findOrFail($id);
            $oldData = $penilaian->toArray();
            $user = Auth::user();

            // Cek jadwal AMI - Super Admin dan Admin LPM dikecualikan dari pengecekan
            if (!Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
                $isJadwalActive = $this->isJadwalActive($penilaian->prodi, $penilaian->periode_atau_tahun);
                if (!$isJadwalActive) {
                    throw new \Exception('Jadwal AMI untuk periode ini telah berakhir atau belum di mulai. Tidak dapat memperbarui penilaian.');
                }
            }

            // Tidak ada yang boleh mengubah penilaian dengan status DISETUJUI
            if ($penilaian->status == PenilaianKriteria::STATUS_DISETUJUI) {
                throw new \Exception('Penilaian dengan status disetujui tidak dapat diubah.');
            }

            // Cek akses berdasarkan peran dan status penilaian
            if (Auth::user()->hasActiveRole('Auditor') && !Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
                // Auditor hanya bisa mengubah penilaian dengan status DIAJUKAN
                if ($penilaian->status != PenilaianKriteria::STATUS_DIAJUKAN) {
                    throw new \Exception('Auditor hanya dapat mengubah penilaian yang statusnya telah diajukan.');
                }
            }

            if (Auth::user()->hasActiveRole('Admin Prodi') && !Auth::user()->hasActiveRole('Super Admin') && !Auth::user()->hasActiveRole('Admin LPM')) {
                // Admin bisa mengubah penilaian dengan status REVISI atau STATUS_PENILAIAN
                if (
                    $penilaian->status != PenilaianKriteria::STATUS_REVISI &&
                    $penilaian->status != PenilaianKriteria::STATUS_PENILAIAN
                ) {
                    throw new \Exception('Admin hanya dapat mengubah penilaian yang statusnya revisi atau penilaian.');
                }
            }

            // Update data penilaian (Super Admin, Admin LPM, dan Admin dengan status REVISI atau STATUS_PENILAIAN)
            // if (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM') || 
            //     (Auth::user()->hasActiveRole('Admin Prodi') && 
            //      ($penilaian->status == PenilaianKriteria::STATUS_REVISI || 
            //       $penilaian->status == PenilaianKriteria::STATUS_PENILAIAN))) {
            //     $penilaian->nilai = $request->nilai;
            //     $penilaian->bobot = $request->bobot;
            //     $penilaian->tertimbang = $request->nilai * $request->bobot;

            //     // Tentukan sebutan berdasarkan nilai
            //     if ($request->nilai == 4) {
            //         $penilaian->sebutan = 'Sangat Baik';
            //     } elseif ($request->nilai >= 3 && $request->nilai < 4) {
            //         $penilaian->sebutan = 'Baik';
            //     } elseif ($request->nilai >= 2 && $request->nilai < 3) {
            //         $penilaian->sebutan = 'Cukup';
            //     } elseif ($request->nilai >= 1 && $request->nilai < 2) {
            //         $penilaian->sebutan = 'Kurang';
            //     } else {
            //         $penilaian->sebutan = 'Sangat Kurang';
            //     }
            // }

            // Set nilai auditor jika user adalah auditor (dengan status DIAJUKAN), Super Admin, atau Admin LPM
            if ((Auth::user()->hasActiveRole('Auditor') && $penilaian->status == PenilaianKriteria::STATUS_DIAJUKAN)
                || Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM')
            ) {
                $penilaian->nilai = $request->nilai_auditor ?? $request->nilai;
                $penilaian->revisi = $request->revisi;
                $penilaian->bobot = $request->bobot;
                $penilaian->tertimbang = $request->nilai * $request->bobot;

                // Tangani konfirmasi revisi
                if ($request->has('revisi_confirmed')) {
                    $penilaian->revisi_confirmed = true;
                } else {
                    $penilaian->revisi_confirmed = false;
                }

                // Tentukan sebutan berdasarkan nilai
                if ($request->nilai == 4) {
                    $penilaian->sebutan = 'Sangat Baik';
                } elseif ($request->nilai >= 3 && $request->nilai < 4) {
                    $penilaian->sebutan = 'Baik';
                } elseif ($request->nilai >= 2 && $request->nilai < 3) {
                    $penilaian->sebutan = 'Cukup';
                } elseif ($request->nilai >= 1 && $request->nilai < 2) {
                    $penilaian->sebutan = 'Kurang';
                } else {
                    $penilaian->sebutan = 'Sangat Kurang';
                }

                $penilaian->tanggal_pemenuhan = $request->tanggal_pemenuhan;
                $penilaian->penanggung_jawab = $request->penanggung_jawab;
                $penilaian->status_temuan = $request->status_temuan;
                $penilaian->hasil_ami = $request->hasil_ami;
                $penilaian->output = $request->output;
                $penilaian->akar_penyebab_masalah = $request->akar_penyebab_masalah;
                $penilaian->tinjauan_efektivitas_koreksi = $request->tinjauan_efektivitas_koreksi;
                $penilaian->kesimpulan = $request->kesimpulan;
            }

            $penilaian->save();

            DB::commit();

            // Log aktivitas
            ActivityLogService::log(
                'updated',
                'penilaian_kriteria',
                'Updated penilaian kriteria',
                $penilaian,
                $oldData,
                $penilaian->fresh()->toArray()
            );

            return redirect()->route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                'kriteriaDokumenId' => $penilaian->kriteria_dokumen_id,
                'prodi' => $penilaian->prodi
            ])->with('success', 'Penilaian berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Update penilaian error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Gagal memperbarui penilaian: ' . $e->getMessage());
        }
    }
}
