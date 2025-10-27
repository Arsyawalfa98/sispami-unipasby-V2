<?php

namespace App\Http\Controllers;

use App\Models\PenilaianKriteria;
use App\Models\KriteriaDokumen;
use App\Models\JadwalAmi;
use App\Models\KelolaKebutuhanKriteriaDokumen;
use App\Models\PemenuhanDokumen;
use App\Models\Jenjang;
use App\Models\Siakad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\PemenuhanDokumen\PemenuhanDokumenService;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class KelengkapanDokumenController extends Controller
{
    protected $pemenuhanDokumenService;

    public function __construct(PemenuhanDokumenService $pemenuhanDokumenService)
    {
        $this->pemenuhanDokumenService = $pemenuhanDokumenService;
    }

    public function exportExcel($lembagaId, $jenjangId, Request $request)
    {
        try {
            $selectedProdi = $request->prodi;
            $kode = $request->get('kode', '');
            $tanggal = $request->get('tanggal', '');
            $revisi = $request->get('revisi', '');

            if (!$selectedProdi) {
                return redirect()->back()->with('error', 'Program studi harus dipilih');
            }

            // Gunakan service yang sama dengan yang digunakan di view
            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                Auth::user()
            );

            // Ambil tim_auditor dari prodiList dengan pemisahan ketua dan anggota
            $auditorKetua = '';
            $auditorAnggota = '';
            if (isset($data['prodiList'])) {
                foreach ($data['prodiList'] as $prodi) {
                    if ($prodi['prodi'] === $selectedProdi) {
                        if (isset($prodi['tim_auditor_detail'])) {
                            $auditorKetua = $prodi['tim_auditor_detail']['ketua'] ?? '';
                            $auditorAnggota = isset($prodi['tim_auditor_detail']['anggota'])
                                ? implode(', ', $prodi['tim_auditor_detail']['anggota'])
                                : '';
                        } else {
                            // Fallback ke tim_auditor string jika detail tidak ada
                            $auditorAnggota = $prodi['tim_auditor'] ?? '';
                        }
                        break;
                    }
                }
            }

            // Gunakan data yang sama dengan showDetail untuk kriteria dokumen
            $detailData = $this->prepareDetailData($lembagaId, $jenjangId, $selectedProdi);
            $dokumenDetail = $detailData['dokumenDetail'];
            $headerData = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->first();

            // Mendapatkan nama prodi saja (contoh: dari "J.2 - Farmasi (S1)" menjadi "Farmasi")
            $prodiName = '';
            $kodeUnit = '';
            if (strpos($selectedProdi, '-') !== false) {
                $prodiParts = explode('-', $selectedProdi);
                $prodiName = trim($prodiParts[1]); // Ambil bagian setelah kode
                $kodeUnit = trim($prodiParts[0]);  // Ambil kode unit

                // Jika masih ada tanda kurung, ambil yang sebelum kurung
                if (strpos($prodiName, '(') !== false) {
                    $prodiNameParts = explode('(', $prodiName);
                    $prodiName = trim($prodiNameParts[0]);
                }
            } else {
                $prodiName = $selectedProdi;
            }

            // Dapatkan nama Kaprodi dari model Siakad
            $kaprodiName = '';
            if (!empty($kodeUnit)) {
                $kaprodiName = Siakad::getKaprodiByKodeUnit($kodeUnit);
            }

            // Gabungkan nama prodi dengan nama Kaprodi jika tersedia
            $prodiNameWithKaprodi = $prodiName;
            if (!empty($kaprodiName)) {
                $prodiNameWithKaprodi .= ' (' . $kaprodiName . ')';
            }

            // Buat spreadsheet baru
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set judul worksheet
            $sheet->setTitle('Kelengkapan Dokumen');

            // Set default font style
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

            // Definisi style untuk border
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];

            // =========== BAGIAN HEADER =============
            // Gabungkan cell untuk logo
            $sheet->mergeCells('A1:A3');

            // Universitas dan alamat
            $sheet->setCellValue('B1', 'UNIVERSITAS PGRI ADI BUANA Surabaya');
            $sheet->getStyle('B1')->getFont()->setBold(true);

            $sheet->setCellValue('B2', 'Jl. Dukuh Menanggal XII, Surabaya, 60234');

            $sheet->setCellValue('B3', 'Telp. (031) 8289637, Fax. (031) 8289637');

            // Range cells B1-E3 dipisahkan menjadi 3 baris yang masing-masing digabungkan (B-E)
            $sheet->mergeCells('B1:E1');
            $sheet->mergeCells('B2:E2');
            $sheet->mergeCells('B3:E3');

            // Tambahan pengaturan lebar kolom untuk header
            $sheet->getColumnDimension('B')->setWidth(45);    // Kolom text header Universitas
            $sheet->getColumnDimension('F')->setWidth(14);    // Kolom KODE
            $sheet->getColumnDimension('G')->setWidth(16);    // Kolom tanggal & revisi - diperlebar

            // KODE
            $sheet->mergeCells('F1:G1');
            $sheet->setCellValue('F1', 'KODE');
            $sheet->getStyle('F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F1')->getFont()->setBold(true);

            // PERUBAHAN #1: Hapus isi FM/02/LPM/03 (dikosongkan)
            $sheet->mergeCells('F2:G2');
            $sheet->setCellValue('F2', $kode);
            $sheet->getStyle('F2')->getFont()->setBold(true);
            $sheet->getStyle('F2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Tanggal & Nilai - PERUBAHAN #2: Hapus nilai tanggal (dikosongkan)
            $sheet->setCellValue('F3', 'Tanggal');
            $sheet->setCellValue('G3', $tanggal);

            $sheet->getStyle('G3')->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(3)->setRowHeight(22);

            // Revisi & Nilai - PERUBAHAN #2: Hapus nilai revisi (dikosongkan)
            $sheet->setCellValue('F4', 'Revisi');
            $sheet->setCellValue('G4', $revisi);

            $sheet->getStyle('G4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Logo
            $logoPath = public_path('img/picture_logo.png');
            if (!file_exists($logoPath)) {
                $logoPath = public_path('public/img/picture_logo.png');
                if (!file_exists($logoPath)) {
                    $logoPath = public_path('storage/img/picture_logo.png');
                }
            }

            // Membuat border dulu agar logo berada di dalam cell
            $sheet->getStyle('A1:A3')->applyFromArray($borderStyle);

            // Atur tinggi dan lebar cell logo
            $sheet->getRowDimension(1)->setRowHeight(25);
            $sheet->getRowDimension(2)->setRowHeight(22);
            $sheet->getRowDimension(3)->setRowHeight(22);
            $sheet->getColumnDimension('A')->setWidth(12); // Perlebar kolom logo

            if (file_exists($logoPath)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($logoPath);
                $drawing->setCoordinates('A1');
                $drawing->setHeight(55); // Ukuran logo disesuaikan
                $drawing->setWidth(55);  // Ukuran logo disesuaikan
                $drawing->setOffsetX(5); // Hanya sedikit offset
                $drawing->setOffsetY(5); // Hanya sedikit offset
                $drawing->setWorksheet($spreadsheet->getActiveSheet());
            }

            // BUKU 5/FORM KELENGKAPAN DOKUMEN
            $sheet->setCellValue('A4', 'BUKU 5');
            $sheet->getStyle('A4')->getFont()->setBold(true);
            $sheet->getStyle('A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B4:E4');
            $sheet->setCellValue('B4', 'FORM KELENGKAPAN DOKUMEN (CHECKLIST)');
            $sheet->getStyle('B4')->getFont()->setBold(true);

            // Set tinggi baris minimal untuk header
            $sheet->getRowDimension(1)->setRowHeight(24);
            $sheet->getRowDimension(2)->setRowHeight(22);
            $sheet->getRowDimension(3)->setRowHeight(22);
            $sheet->getRowDimension(4)->setRowHeight(22);

            // Border untuk header
            $sheet->getStyle('A1:G4')->applyFromArray($borderStyle);

            // Baris kosong
            $sheet->mergeCells('A5:G5');

            // Judul utama
            $sheet->mergeCells('A6:G6');
            $sheet->setCellValue('A6', 'KELENGKAPAN DOKUMEN/CHECKLIST');
            $sheet->getStyle('A6')->getFont()->setBold(true);
            $sheet->getStyle('A6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Baris kosong
            $sheet->mergeCells('A7:G7');

            // =========== BAGIAN AUDITEE =============
            // Header Auditee & Tahap Audit
            $row = 8;
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':G' . $row);
            $sheet->setCellValue('A' . $row, 'Auditee');
            $sheet->setCellValue('E' . $row, 'Tahap Audit');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);

            // PERUBAHAN UNTUK ISI AUDITEE: Menggunakan nama prodi dengan nama Kaprodi
            $row++;
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':G' . $row);
            $sheet->setCellValue('A' . $row, $prodiNameWithKaprodi); // Gunakan nama prodi + nama Kaprodi
            // Tahap Audit dibiarkan kosong

            // Lokasi, Ruang Lingkup, Tanggal Audit
            $row++;
            $sheet->setCellValue('A' . $row, 'Lokasi');
            $sheet->mergeCells('B' . $row . ':D' . $row);
            $sheet->setCellValue('B' . $row, 'Ruang Lingkup');
            $sheet->mergeCells('E' . $row . ':G' . $row);
            $sheet->setCellValue('E' . $row, 'Tanggal Audit');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);

            // Baris kosong untuk isian manual
            $row++;
            $sheet->mergeCells('A' . $row . ':A' . $row);
            $sheet->mergeCells('B' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':G' . $row);
            $sheet->getRowDimension($row)->setRowHeight(60); // Baris tinggi untuk isian

            // Informasi petugas
            $row++;
            $sheet->setCellValue('A' . $row, 'Wakil Auditee');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':G' . $row);
            // Wakil Auditee dibiarkan kosong

            $row++;
            $sheet->setCellValue('A' . $row, 'Auditor Ketua');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':G' . $row);
            $sheet->setCellValue('B' . $row, $auditorKetua); // Use separated ketua

            $row++;
            $sheet->setCellValue('A' . $row, 'Auditor Anggota');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':G' . $row);
            $sheet->setCellValue('B' . $row, $auditorAnggota); // Use separated anggota

            // Border untuk tabel Auditee
            $sheet->getStyle('A8:G' . $row)->applyFromArray($borderStyle);

            // Baris kosong
            $row++;

            // =========== TABEL CHECKLIST =============
            // Header tabel checklist
            $row++;
            $startChecklist = $row;

            // Header kolom untuk checklist
            $sheet->setCellValue('A' . $row, 'No');
            $sheet->setCellValue('B' . $row, 'Aspek');
            $sheet->setCellValue('C' . $row, 'Ada');
            $sheet->setCellValue('D' . $row, 'Tidak ada');
            $sheet->setCellValue('E' . $row, 'Nama Dokumen');
            $sheet->setCellValue('F' . $row, 'Keterangan');
            $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);

            // Nomor kolom (1,2,3,4,5,6)
            $row++;
            $sheet->setCellValue('A' . $row, '1');
            $sheet->setCellValue('B' . $row, '2');
            $sheet->setCellValue('C' . $row, '3');
            $sheet->setCellValue('D' . $row, '4');
            $sheet->setCellValue('E' . $row, '5');
            $sheet->setCellValue('F' . $row, '6');
            $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);

            // Atur pengaturan global untuk teks panjang
            $sheet->getStyle('A1:G1000')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

            // Mengaktifkan text wrapping global untuk kolom Aspek dan lainnya
            $sheet->getStyle('B1:B1000')->getAlignment()->setWrapText(true);
            $sheet->getStyle('E1:F1000')->getAlignment()->setWrapText(true);

            // Isi tabel berdasarkan kriteria dari data
            $row++;

            // ===== KODE YANG DIUBAH MULAI DARI SINI =====
            // Tambahkan global counter untuk indikator
            $globalIndikatorCounter = 1;

            // Proses dan tampilkan data dari dokumenDetail
            foreach ($dokumenDetail as $kriteria => $documents) {
                // Kelompokkan dokumen berdasarkan element
                $elementGroups = [];

                // Kelompokkan dokumen berdasarkan kode element
                foreach ($documents as $dokumen) {
                    $kode = $dokumen->kode ?? '';
                    if (!isset($elementGroups[$kode])) {
                        $elementGroups[$kode] = [
                            'element' => $dokumen->element ?? '',
                            'dokumen' => []
                        ];
                    }
                    $elementGroups[$kode]['dokumen'][] = $dokumen;
                }

                // Tampilkan setiap element dan indikatornya
                foreach ($elementGroups as $kode => $group) {
                    // Header element
                    $sheet->setCellValue('A' . $row, $kode);              // Kode element (C1-A, C2-A)
                    $sheet->setCellValue('B' . $row, $group['element']);  // Element (ELEMENT C1-A, ELEMENT C2-A)

                    // Style untuk header element
                    $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => 'E6E6E6',
                            ],
                        ],
                    ]);

                    $row++;

                    // Grup dokumen berdasarkan indikator
                    $indikatorGroups = [];
                    foreach ($group['dokumen'] as $dokumen) {
                        $indikator = $dokumen->indikator ?? '';
                        if (!isset($indikatorGroups[$indikator])) {
                            $indikatorGroups[$indikator] = [];
                        }

                        // Simpan informasi dokumen
                        $dokumenInfo = [
                            'nama_dokumen' => $dokumen->nama_dokumen ?? '',
                            'tambahan_informasi' => $dokumen->tambahan_informasi ?? ''
                        ];

                        // Jika ada multiple nama dokumen dan informasi tambahan
                        if (isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0) {
                            $dokumenInfo['multiple'] = true;
                            $dokumenInfo['nama_dokumens'] = $dokumen->nama_dokumens;
                            $dokumenInfo['tambahan_informasis'] = $dokumen->tambahan_informasis ?? [];
                        }

                        $indikatorGroups[$indikator][] = $dokumenInfo;
                    }

                    // Proses setiap grup indikator
                    foreach ($indikatorGroups as $indikator => $dokumenList) {
                        $startRow = $row; // Simpan baris awal untuk merge cells nanti

                        // Cek apakah ada multiple dokumen
                        $multipleFilesExist = false;
                        $totalFiles = 0;

                        foreach ($dokumenList as $dokumenInfo) {
                            if (isset($dokumenInfo['multiple']) && $dokumenInfo['multiple']) {
                                $multipleFilesExist = true;
                                $totalFiles += count($dokumenInfo['nama_dokumens']);
                            } else {
                                $totalFiles++;
                            }
                        }

                        // Jika hanya ada satu file, proses seperti biasa
                        if ($totalFiles == 1) {
                            $dokumenInfo = $dokumenList[0];

                            $sheet->setCellValue('A' . $row, $globalIndikatorCounter); // Gunakan global counter
                            $sheet->setCellValue('B' . $row, $indikator);

                            if (isset($dokumenInfo['multiple']) && $dokumenInfo['multiple']) {
                                $sheet->setCellValue('E' . $row, $dokumenInfo['nama_dokumens'][0] ?? '');
                                $sheet->setCellValue('F' . $row, $dokumenInfo['tambahan_informasis'][0] ?? '');
                            } else {
                                $sheet->setCellValue('E' . $row, $dokumenInfo['nama_dokumen']);
                                $sheet->setCellValue('F' . $row, $dokumenInfo['tambahan_informasi']);
                            }

                            $row++;
                            $globalIndikatorCounter++; // Increment global counter
                        }
                        // Jika ada multiple file, merge cell untuk kolom No, Aspek, Ada, Tidak ada
                        else {
                            // Proses file pertama
                            $sheet->setCellValue('A' . $row, $globalIndikatorCounter); // Gunakan global counter
                            $sheet->setCellValue('B' . $row, $indikator);

                            $currentRow = $row;

                            // Proses semua file dari semua dokumen
                            foreach ($dokumenList as $dokumenInfo) {
                                if (isset($dokumenInfo['multiple']) && $dokumenInfo['multiple']) {
                                    // Proses multiple files dalam satu dokumen
                                    for ($i = 0; $i < count($dokumenInfo['nama_dokumens']); $i++) {
                                        $sheet->setCellValue('E' . $currentRow, $dokumenInfo['nama_dokumens'][$i] ?? '');
                                        $sheet->setCellValue('F' . $currentRow, $dokumenInfo['tambahan_informasis'][$i] ?? '');

                                        // Jika bukan file terakhir, pindah ke baris berikutnya
                                        if ($currentRow < $startRow + $totalFiles - 1) {
                                            $currentRow++;
                                        }
                                    }
                                } else {
                                    // Proses single file
                                    $sheet->setCellValue('E' . $currentRow, $dokumenInfo['nama_dokumen']);
                                    $sheet->setCellValue('F' . $currentRow, $dokumenInfo['tambahan_informasi']);

                                    // Jika bukan dokumen terakhir, pindah ke baris berikutnya
                                    if ($currentRow < $startRow + $totalFiles - 1) {
                                        $currentRow++;
                                    }
                                }
                            }

                            // Merge cells untuk kolom A (No)
                            if ($totalFiles > 1) {
                                $sheet->mergeCells('A' . $startRow . ':A' . ($startRow + $totalFiles - 1));
                                $sheet->mergeCells('B' . $startRow . ':B' . ($startRow + $totalFiles - 1));
                                $sheet->mergeCells('C' . $startRow . ':C' . ($startRow + $totalFiles - 1));
                                $sheet->mergeCells('D' . $startRow . ':D' . ($startRow + $totalFiles - 1));

                                // Center alignment untuk merged cells
                                $sheet->getStyle('A' . $startRow . ':D' . ($startRow + $totalFiles - 1))->getAlignment()
                                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                            }

                            $row = $startRow + $totalFiles;
                            $globalIndikatorCounter++; // Increment global counter hanya sekali per grup indikator
                        }

                        // Aktifkan wrap text untuk teks panjang
                        $sheet->getStyle('B' . $startRow)->getAlignment()->setWrapText(true);
                        $sheet->getStyle('E' . $startRow . ':F' . ($row - 1))->getAlignment()->setWrapText(true);

                        // Sesuaikan tinggi baris berdasarkan panjang teks
                        for ($i = $startRow; $i < $row; $i++) {
                            $textLengths = [
                                ($i == $startRow) ? strlen($indikator) : 0,
                                strlen($sheet->getCell('E' . $i)->getValue()),
                                strlen($sheet->getCell('F' . $i)->getValue())
                            ];
                            $maxTextLength = max($textLengths);

                            // Hitung perkiraan jumlah baris yang dibutuhkan
                            $estimatedLines = ceil($maxTextLength / 50);
                            $rowHeight = max(22, 22 + ($estimatedLines - 1) * 18);

                            // Set tinggi baris
                            $sheet->getRowDimension($i)->setRowHeight($rowHeight);
                        }
                    }
                }
            }
            // ===== KODE YANG DIUBAH SAMPAI SINI =====

            // Border untuk tabel checklist
            $sheet->getStyle('A' . $startChecklist . ':F' . ($row - 1))->applyFromArray($borderStyle);

            // Set lebar kolom berdasarkan kebutuhan
            $sheet->getColumnDimension('A')->setWidth(8);      // No
            $sheet->getColumnDimension('B')->setWidth(50);     // Aspek
            $sheet->getColumnDimension('C')->setWidth(6);      // Ada
            $sheet->getColumnDimension('D')->setWidth(10);     // Tidak ada
            $sheet->getColumnDimension('E')->setWidth(28);     // Nama Dokumen
            $sheet->getColumnDimension('F')->setWidth(16);     // Keterangan
            $sheet->getColumnDimension('G')->setWidth(5);      // Kolom kosong di kanan

            // Set alignment center untuk kolom tertentu
            $sheet->getStyle('A' . $startChecklist . ':A' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $startChecklist . ':D' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Persiapkan semua cell untuk autofit
            for ($i = $startChecklist; $i < $row; $i++) {
                // Tambahan tinggi baris untuk memastikan semua teks terlihat
                $currentHeight = $sheet->getRowDimension($i)->getRowHeight();
                if ($currentHeight < 30) { // Jika tinggi baris kurang dari 30, sesuaikan
                    $sheet->getRowDimension($i)->setRowHeight(30);
                }
            }

            // Set proper content type and filename for download
            $filename = 'Kelengkapan_Dokumen_' . str_replace(' ', '_', $prodiName) . '_' . date('Y-m-d') . '.xlsx';

            // Create Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // Save to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'excel');
            $writer->save($tempFile);

            // Return the file as download
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekspor Excel: ' . $e->getMessage());
        }
    }

    public function exportPdf($lembagaId, $jenjangId, Request $request)
    {
        try {
            $selectedProdi = $request->prodi;
            $kode = $request->get('kode', '');
            $tanggal = $request->get('tanggal', '');
            $revisi = $request->get('revisi', '');

            if (!$selectedProdi) {
                return redirect()->back()->with('error', 'Program studi harus dipilih');
            }

            // Gunakan service yang sama dengan yang digunakan di view
            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                Auth::user()
            );

            // Ambil tim_auditor dari prodiList dengan pemisahan ketua dan anggota
            $auditorKetua = '';
            $auditorAnggota = '';
            if (isset($data['prodiList'])) {
                foreach ($data['prodiList'] as $prodi) {
                    if ($prodi['prodi'] === $selectedProdi) {
                        if (isset($prodi['tim_auditor_detail'])) {
                            $auditorKetua = $prodi['tim_auditor_detail']['ketua'] ?? '';
                            $auditorAnggota = isset($prodi['tim_auditor_detail']['anggota'])
                                ? implode(', ', $prodi['tim_auditor_detail']['anggota'])
                                : '';
                        } else {
                            // Fallback ke tim_auditor string jika detail tidak ada
                            $auditorAnggota = $prodi['tim_auditor'] ?? '';
                        }
                        break;
                    }
                }
            }

            // Ambil data detail dokumen
            $detailData = $this->prepareDetailData($lembagaId, $jenjangId, $selectedProdi);
            $dokumenDetail = $detailData['dokumenDetail'];

            // Get header data
            $headerData = KriteriaDokumen::where([
                'lembaga_akreditasi_id' => $lembagaId,
                'jenjang_id' => $jenjangId
            ])->first();

            // Mendapatkan nama prodi saja (contoh: dari "J.2 - Farmasi (S1)" menjadi "Farmasi")
            $prodiName = '';
            $kodeUnit = '';
            if (strpos($selectedProdi, '-') !== false) {
                $prodiParts = explode('-', $selectedProdi);
                $prodiName = trim($prodiParts[1]); // Ambil bagian setelah kode
                $kodeUnit = trim($prodiParts[0]);  // Ambil kode unit

                // Jika masih ada tanda kurung, ambil yang sebelum kurung
                if (strpos($prodiName, '(') !== false) {
                    $prodiNameParts = explode('(', $prodiName);
                    $prodiName = trim($prodiNameParts[0]);
                }
            } else {
                $prodiName = $selectedProdi;
            }

            // Dapatkan nama Kaprodi dari model Siakad
            $kaprodiName = '';
            if (!empty($kodeUnit)) {
                $kaprodiName = Siakad::getKaprodiByKodeUnit($kodeUnit);
            }

            // Gabungkan nama prodi dengan nama Kaprodi jika tersedia
            $prodiNameWithKaprodi = $prodiName;
            if (!empty($kaprodiName)) {
                $prodiNameWithKaprodi .= ' (' . $kaprodiName . ')';
            }

            // Siapkan data untuk view
            $viewData = [
                'dokumenDetail' => $dokumenDetail,
                'headerData' => $headerData,
                'prodiName' => $prodiNameWithKaprodi, // Gunakan prodi dengan kaprodi
                'auditorKetua' => $auditorKetua,      // Add separated ketua
                'auditorAnggota' => $auditorAnggota,  // Add separated anggota
                'tahun' => $headerData->periode_atau_tahun ?? date('Y'),
                'globalIndikatorCounter' => 1, // Tambahkan counter global
                'kode' => $kode,
                'tanggal' => $tanggal,
                'revisi' => $revisi
            ];

            // Generate PDF
            $pdf = \PDF::loadView('kelengkapan-dokumen.export-pdf', $viewData);
            $pdf->setPaper('a4', 'landscape');

            // Set filename
            $filename = 'Kelengkapan_Dokumen_' . str_replace(' ', '_', $prodiName) . '_' . date('Y-m-d') . '.pdf';

            // PERUBAHAN: Stream PDF di browser untuk preview
            return $pdf->stream($filename);

            // Untuk download langsung, gunakan:
            // return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekspor PDF: ' . $e->getMessage());
        }
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->only(['search']);
        $perPage = $request->get('per_page', 5); // Default 5 item per halaman
        $kriteriaDokumen = $this->pemenuhanDokumenService->getFilteredData($filters, $perPage);

        // Dapatkan semua nilai status yang unik untuk dropdown
        $statusOptions = $this->getUniqueStatuses($kriteriaDokumen);

        // Dapatkan semua tahun yang unik
        $yearOptions = $this->getUniqueYears($kriteriaDokumen);

        // Dapatkan semua jenjang dari model Jenjang
        $jenjangOptions = Jenjang::pluck('nama', 'id')->toArray();

        // Filter status di sisi controller setelah data diambil dari service
        $selectedStatus = $request->get('status');
        $selectedYear = $request->get('year');
        $selectedJenjang = $request->get('jenjang');

        if ($selectedStatus && $selectedStatus !== 'all') {
            // Filter collection berdasarkan status yang dipilih
            $kriteriaDokumen = $this->filterByStatus($kriteriaDokumen, $selectedStatus);
        }

        if ($selectedYear && $selectedYear !== 'all') {
            // Filter collection berdasarkan tahun yang dipilih
            $kriteriaDokumen = $this->filterByYear($kriteriaDokumen, $selectedYear);
        }

        if ($selectedJenjang && $selectedJenjang !== 'all') {
            // Filter collection berdasarkan jenjang yang dipilih
            $kriteriaDokumen = $this->filterByJenjang($kriteriaDokumen, $selectedJenjang);
        }

        return view('kelengkapan-dokumen.index', compact(
            'kriteriaDokumen',
            'statusOptions',
            'yearOptions',
            'jenjangOptions',
            'selectedStatus',
            'selectedYear',
            'selectedJenjang'
        ));
    }

    /**
     * Mendapatkan semua nilai status yang unik dari dokumen
     */
    private function getUniqueStatuses($kriteriaDokumen)
    {
        $statuses = collect();

        foreach ($kriteriaDokumen as $item) {
            foreach ($item->filtered_details as $detail) {
                if (isset($detail['status'])) {
                    $statuses->push($detail['status']);
                }
            }
        }

        return $statuses->unique()->values()->all();
    }

    /**
     * Mendapatkan semua tahun unik dari dokumen
     */
    private function getUniqueYears($kriteriaDokumen)
    {
        $years = collect();

        foreach ($kriteriaDokumen as $item) {
            if (isset($item->periode_atau_tahun)) {
                $years->push($item->periode_atau_tahun);
            }
        }

        return $years->unique()->values()->all();
    }

    /**
     * Filter collection berdasarkan status
     */
    private function filterByStatus($kriteriaDokumen, $status)
    {
        // Kita perlu mengkloning collection untuk menghindari perubahan pada collection asli
        $filteredCollection = new LengthAwarePaginator(
            $kriteriaDokumen->getCollection()->filter(function ($item) use ($status) {
                // Filter detail berdasarkan status
                $filteredDetails = $item->filtered_details->filter(function ($detail) use ($status) {
                    return isset($detail['status']) && $detail['status'] === $status;
                });

                // Jika setelah filtering masih ada detail, simpan dan update filtered_details
                if ($filteredDetails->isNotEmpty()) {
                    $item->filtered_details = $filteredDetails;
                    return true;
                }

                return false;
            }),
            $kriteriaDokumen->total(), // Total asli dari paginator
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );

        return $filteredCollection;
    }

    /**
     * Filter collection berdasarkan tahun
     */
    private function filterByYear($kriteriaDokumen, $year)
    {
        // Filter berdasarkan tahun
        $filteredCollection = new LengthAwarePaginator(
            $kriteriaDokumen->getCollection()->filter(function ($item) use ($year) {
                return isset($item->periode_atau_tahun) && $item->periode_atau_tahun == $year;
            }),
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );

        return $filteredCollection;
    }

    /**
     * Filter collection berdasarkan jenjang
     */
    private function filterByJenjang($kriteriaDokumen, $jenjangNama)
    {
        // Filter berdasarkan jenjang
        $filteredCollection = new LengthAwarePaginator(
            $kriteriaDokumen->getCollection()->filter(function ($item) use ($jenjangNama) {
                return isset($item->jenjang) && $item->jenjang->nama === $jenjangNama;
            }),
            $kriteriaDokumen->total(),
            $kriteriaDokumen->perPage(),
            $kriteriaDokumen->currentPage(),
            ['path' => $kriteriaDokumen->path()]
        );

        return $filteredCollection;
    }

    public function showGroup($lembagaId, $jenjangId)
    {
        try {
            $user = Auth::user();
            $selectedProdi = request('prodi');

            // Dapatkan parameter filter dari request
            $filterStatus = request('status');
            $filterYear = request('year');
            $filterJenjang = request('jenjang');

            $data = $this->pemenuhanDokumenService->getShowGroupData(
                $lembagaId,
                $jenjangId,
                $selectedProdi,
                $user
            );

            // Filter daftar prodi berdasarkan parameter yang diteruskan dari halaman index
            $filteredProdiList = $data['prodiList'];

            if ($filterStatus && $filterStatus !== 'all') {
                $filteredProdiList = $filteredProdiList->filter(function ($prodi) use ($filterStatus) {
                    return isset($prodi['status']) && $prodi['status'] === $filterStatus;
                });
            }

            // Tampilkan parameter filter di view untuk debugging jika diperlukan
            $filterParams = [
                'status' => $filterStatus,
                'year' => $filterYear,
                'jenjang' => $filterJenjang
            ];

            // Simpan filter params untuk digunakan di halaman lain
            session(['filter_params' => $filterParams]);

            // Cek status penilaian untuk prodi yang dipilih
            $penilaianStatus = null;
            if ($selectedProdi) {
                $penilaian = PenilaianKriteria::where('prodi', $selectedProdi)
                    ->whereHas('kriteriaDokumen', function ($query) use ($lembagaId, $jenjangId) {
                        $query->where('lembaga_akreditasi_id', $lembagaId)
                            ->where('jenjang_id', $jenjangId);
                    })
                    ->first();

                $penilaianStatus = $penilaian ? $penilaian->status : PenilaianKriteria::STATUS_DRAFT;

                // Prepare detail data when prodi is selected
                $detailData = $this->prepareDetailData($lembagaId, $jenjangId, $selectedProdi);
                $dokumenDetail = $detailData['dokumenDetail'];
                $totalByKriteria = $detailData['totalByKriteria'];
                $totalKumulatif = $detailData['totalKumulatif'];
            } else {
                // Empty data when no prodi is selected
                $dokumenDetail = [];
                $totalByKriteria = [];
                $totalKumulatif = [];
            }

            // Add periode_atau_tahun to prodiList
            foreach ($data['prodiList'] as &$prodi) {
                $prodi['prodi_dengan_tahun'] = $prodi['prodi'] . ' - ' . ($data['headerData']->periode_atau_tahun ?? date('Y'));
            }

            return view('kelengkapan-dokumen.show-group', [
                'dokumenDetail' => $dokumenDetail,
                'totalByKriteria' => $totalByKriteria,
                'totalKumulatif' => $totalKumulatif,
                'headerData' => $data['headerData'],
                'prodiList' => $filteredProdiList,
                'lembagaId' => $lembagaId,
                'jenjangId' => $jenjangId,
                'selectedProdi' => $selectedProdi,
                'penilaianStatus' => $penilaianStatus,
                'filterParams' => $filterParams
            ]);
        } catch (\Exception $e) {
            return redirect()->route('kelengkapan-dokumen.index')
                ->with('error', $e->getMessage());
        }
    }

    private function getStatusKelengkapan($nilai)
    {
        if ($nilai === 4) {
            return 'Sangat Lengkap';
        } elseif ($nilai >= 3 && $nilai < 4) {
            return 'Lengkap';
        } elseif ($nilai >= 2 && $nilai < 3) {
            return 'Cukup Lengkap';
        } elseif ($nilai >= 1 && $nilai < 2) {
            return 'Kurang Lengkap';
        } elseif ($nilai >= 0 && $nilai < 1) {
            return 'Tidak Lengkap';
        } else {
            return '-';
        }
    }

    private function prepareDetailData($lembagaId, $jenjangId, $selectedProdi)
    {
        // Dapatkan kriteria_dokumen_id berdasarkan lembagaId dan jenjangId
        $kriteriaDokumenItems = KriteriaDokumen::where([
            'lembaga_akreditasi_id' => $lembagaId,
            'jenjang_id' => $jenjangId
        ])->with('judulKriteriaDokumen')->get();

        // Ambil semua penilaian untuk prodi tersebut
        $allPenilaian = PenilaianKriteria::whereIn('kriteria_dokumen_id', $kriteriaDokumenItems->pluck('id'))
            ->where('prodi', $selectedProdi)
            ->get();

        // Ambil semua pemenuhan dokumen untuk prodi tersebut
        $allPemenuhanDokumen = PemenuhanDokumen::whereIn('kriteria_dokumen_id', $kriteriaDokumenItems->pluck('id'))
            ->where('prodi', $selectedProdi)
            ->get();

        // Kelompokkan berdasarkan kriteria
        $penilaianByKriteria = [];

        foreach ($kriteriaDokumenItems as $kd) {
            $kriteria = $kd->judulKriteriaDokumen->nama_kriteria_dokumen ?? 'Tidak Diketahui';

            // Skip jika kriteria 'Tidak Diketahui'
            if ($kriteria === 'Tidak Diketahui') {
                continue;
            }

            // PERUBAHAN: Cek apakah penilaian ada, jika tidak buat dummy penilaian
            $penilaian = $allPenilaian->firstWhere('kriteria_dokumen_id', $kd->id);
            
            // PERBAIKAN: Jika tidak ada penilaian, buat objek dummy untuk menampilkan data
            if (!$penilaian) {
                $penilaian = new PenilaianKriteria([
                    'kriteria_dokumen_id' => $kd->id,
                    'prodi' => $selectedProdi,
                    'nilai' => 0,
                    'bobot' => $kd->bobot ?? 0,
                    'tertimbang' => 0,
                    'nilai_auditor' => null,
                    'sebutan' => '-'
                ]);
            }

            // Reset nilai default
            $penilaian->nama_dokumen = null;
            $penilaian->tambahan_informasi = null;

            // Cari data pemenuhan dokumen yang sesuai (bisa ada lebih dari satu)
            $pemenuhanDokumens = $allPemenuhanDokumen->where('kriteria_dokumen_id', $kd->id)->values();

            if ($pemenuhanDokumens->count() > 0) {
                // Gunakan data dari pemenuhan dokumen pertama untuk tampilan utama
                $penilaian->nama_dokumen = $pemenuhanDokumens->first()->nama_dokumen;
                $penilaian->tambahan_informasi = $pemenuhanDokumens->first()->tambahan_informasi;

                // Simpan semua file dokumen dan informasi terkait
                $penilaian->files_dokumen = $pemenuhanDokumens->pluck('file')->toArray();
                $penilaian->nama_dokumens = $pemenuhanDokumens->pluck('nama_dokumen')->toArray();
                $penilaian->tipe_dokumens = $pemenuhanDokumens->pluck('tipe_dokumen')->toArray();
                $penilaian->tambahan_informasis = $pemenuhanDokumens->pluck('tambahan_informasi')->toArray();
            }

            // PERBAIKAN: Selalu cek dari KelolaKebutuhanKriteriaDokumen untuk memastikan data tampil
            if ($penilaian->nama_dokumen === null) {
                $kelolaKebutuhan = KelolaKebutuhanKriteriaDokumen::where('kriteria_dokumen_id', $kd->id)
                    ->first();

                if ($kelolaKebutuhan) {
                    $penilaian->nama_dokumen = $kelolaKebutuhan->nama_dokumen;
                } else {
                    $penilaian->nama_dokumen = '';
                }
            }

            if ($penilaian->tambahan_informasi === null) {
                $penilaian->tambahan_informasi = '';
            }

            // Isi data element, indikator, dll dari kriteria dokumen
            $penilaian->kode = $kd->kode;
            $penilaian->element = $kd->element;
            $penilaian->indikator = $kd->indikator;

            // Ambil bobot dari kriteria_dokumen
            $penilaian->bobot = $kd->bobot ?? $penilaian->bobot;

            // Set status kelengkapan berdasarkan nilai
            $penilaian->status_kelengkapan = $this->getStatusKelengkapan($penilaian->nilai);

            if (!isset($penilaianByKriteria[$kriteria])) {
                $penilaianByKriteria[$kriteria] = collect();
            }

            $penilaian->kriteriaDokumen = $kd;
            $penilaianByKriteria[$kriteria]->push($penilaian);
        }

        // Rest of the method remains the same...
        // Dapatkan semua nama kriteria dan urutkan secara numerik
        $kriteriaNames = array_keys($penilaianByKriteria);

        // Urutkan kriteria berdasarkan nomor kriteria
        usort($kriteriaNames, function ($a, $b) {
            $numA = (int) preg_replace('/[^0-9]/', '', $a);
            $numB = (int) preg_replace('/[^0-9]/', '', $b);
            return $numA - $numB;
        });

        // Buat array penilaian yang sudah diurutkan
        $sortedDokumenDetail = [];
        foreach ($kriteriaNames as $kriteria) {
            $sortedDokumenDetail[$kriteria] = $penilaianByKriteria[$kriteria];
        }

        // Perhitungan total untuk setiap kriteria
        $totalByKriteria = [];
        foreach ($sortedDokumenDetail as $kriteria => $penilaians) {
            $totalNilai = $penilaians->sum('nilai');
            $totalBobot = $penilaians->sum('bobot');
            $totalTertimbang = $penilaians->sum('tertimbang');
            $totalNilaiAuditor = $penilaians->whereNotNull('nilai_auditor')->sum('nilai_auditor');

            $count = $penilaians->count();
            $avgNilai = $count > 0 ? $totalNilai / $count : 0;

            $statusKelengkapan = $this->getStatusKelengkapan($avgNilai);

            $totalByKriteria[$kriteria] = [
                'nilai' => $avgNilai,
                'bobot' => $totalBobot,
                'tertimbang' => $totalTertimbang,
                'nilai_auditor' => $totalNilaiAuditor,
                'status_kelengkapan' => $statusKelengkapan
            ];
        }

        // Perhitungan total kumulatif untuk semua kriteria
        $totalKumulatif = [
            'nilai' => 0,
            'bobot' => 0,
            'tertimbang' => 0,
            'nilai_auditor' => 0
        ];

        foreach ($totalByKriteria as $kriteria => $totals) {
            $totalKumulatif['nilai'] += $sortedDokumenDetail[$kriteria]->sum('nilai');
            $totalKumulatif['bobot'] += $totals['bobot'];
            $totalKumulatif['tertimbang'] += $totals['tertimbang'];
            $totalKumulatif['nilai_auditor'] += $totals['nilai_auditor'];
        }

        // Ambil informasi header
        $headerData = KriteriaDokumen::where([
            'lembaga_akreditasi_id' => $lembagaId,
            'jenjang_id' => $jenjangId
        ])->first();

        return [
            'dokumenDetail' => $sortedDokumenDetail,
            'totalByKriteria' => $totalByKriteria,
            'totalKumulatif' => $totalKumulatif,
            'headerData' => $headerData,
            'lembagaId' => $lembagaId,
            'jenjangId' => $jenjangId,
            'selectedProdi' => $selectedProdi
        ];
    }
}
