<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Permintaan Tindakan Koreksi (PTK)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.3;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 6px;
            text-align: left;
            vertical-align: top;
            font-size: 11px;
        }
        .logo-cell {
            width: 100px;
            text-align: center;
            font-size: 11px;
        }
        .logo-cell div {
            font-size: 8px;
            margin-top: 5px;
        }
        .header-cell {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .kode-label, .kode-value {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 6px;
        }
        .book-cell {
            width: 100px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .title-cell {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .date-cell {
            width: 200px;
            padding: 0;
            font-size: 11px;
        }
        .date-cell table td {
            font-size: 11px;
            padding: 4px;
        }
        .main-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            padding: 15px 0;
        }
        .checkbox {
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            display: inline-block;
            vertical-align: middle;
            text-align: center;
            line-height: 14px;
            font-size: 10px;
        }
        .input-area {
            min-height: 50px;
            font-size: 11px;
            padding: 4px;
        }
        .large-input-area {
            min-height: 80px;
            font-size: 11px;
            padding: 4px;
        }
        .signature-section {
            font-size: 10px;
        }
        .nested-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 11px;
        }
        .nested-table td {
            font-size: 11px;
            padding: 6px;
        }
        .category-section {
            font-size: 11px;
        }
        .category-section span {
            margin-right: 8px;
        }
        .section-label {
            font-weight: bold;
            font-size: 11px;
        }
        .section-instruction {
            font-style: italic;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <table>
            <!-- Header Row -->
            <tr>
                <td rowspan="2" class="logo-cell">
                    <img src="{{ public_path('img/picture_logo.png') }}" alt="Logo Universitas PGRI Adi Buana">
                    <div>Motto Institusi</div>
                </td>
                <td rowspan="2" class="header-cell">
                    <strong>UNIVERSITAS PGRI ADI BUANA Surabaya</strong><br>
                    Jl. Dukuh Menanggal XII, Surabaya, 60234 Telp. (031) 8289637, Fax. (031) 8289637
                </td>
                <td class="kode-label">KODE</td>
            </tr>
            <tr>
                <td class="kode-value">{{ $kode ?? '' }}</td>
            </tr>
            
            <!-- Book Info Row -->
            <tr>
                <td class="book-cell">BUKU 5</td>
                <td class="title-cell">FORMULIR PERMINTAAN TINDAKAN KOREKSI</td>
                <td class="date-cell">
                    <table class="nested-table">
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; width: 30%; border-right: 1px solid #000;">Tanggal</td>
                            <td style="border: none; border-bottom: 1px solid #000;">{{ $tanggal ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; width: 30%; border-right: 1px solid #000;">Revisi</td>
                            <td style="border: none;">{{ $revisi ?? '' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <!-- Main Title -->
            <tr>
                <td colspan="3" class="main-title">PERMINTAAN TINDAKAN KOREKSI (PTK)</td>
            </tr>
            
            <!-- Form Content -->
            <tr>
                <td width="20%">Program Studi/unit kerja</td>
                <td colspan="2">{{ $prodi }}</td>
            </tr>
            <tr>
                <td>Fakultas /Unit Kerja</td>
                <td colspan="2">{{ $fakultas }}</td>
            </tr>
            <tr>
                <td>Kaprodi /Ka unit kerja</td>
                <td colspan="2">{{ $kaprodi }}</td>
            </tr>
            <tr>
                <td>Nama Auditor</td>
                <td style="padding: 0;">
                    <table class="nested-table">
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; width: 30%; border-right: 1px solid #000;">Ketua</td>
                            <td style="border: none; border-bottom: 1px solid #000;">{{ $auditorKetua }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; width: 30%; border-right: 1px solid #000;">Anggota</td>
                            <td style="border: none;">{{ $auditorAnggota }}</td>
                        </tr>
                    </table>
                </td>
                <td>Tanggal Audit : {{ $tanggalAudit }}</td>
            </tr>
            <tr>
                <td>PTK No {{ $dokumen->kode }}</td>
                <td colspan="2" class="category-section">
                    Kategori : 
                    <span>
                        <span class="checkbox">
                            @if($kategoriTemuan['KTS'])
                                <img src="{{ public_path('img/centang.png') }}" style="width: 12px; height: 12px;">
                            @endif
                        </span> KTS 
                    </span>
                    <span>
                        <span class="checkbox">
                            @if($kategoriTemuan['OB'])
                                <img src="{{ public_path('img/centang.png') }}" style="width: 12px; height: 12px;">
                            @endif
                        </span> Observasi (OB)
                    </span>
                    <span>
                        <span class="checkbox">
                            @if($kategoriTemuan['TERCAPAI'])
                                <img src="{{ public_path('img/centang.png') }}" style="width: 12px; height: 12px;">
                            @endif
                        </span> TERCAPAI
                    </span>
                </td>
            </tr>
            <tr>
                <td>Referensi (butir mutu) (indicator)</td>
                <td colspan="2">
                    {{ $kriteriaDokumen->element }} ({{ $kriteriaDokumen->indikator }})
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="section-label">Deskripsi temuan (KTS) (Hasil AMI):</span> 
                    <span class="section-instruction">(diisi oleh auditor)</span><br>
                    <div class="large-input-area">{{ $dokumen->hasil_ami ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    Nama Auditor :<br>
                    Ketua : {{ $auditorKetua }}<br>
                    Anggota : {{ $auditorAnggota }}
                </td>
                <td>Tanda tangan :</td>
                <td>Tanggal audit : {{ $tanggalAudit }}</td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="section-label">AKAR MASALAH :</span> 
                    <span class="section-instruction">(diisi auditee)</span><br>
                    <div class="large-input-area">{{ $dokumen->akar_penyebab_masalah ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    Auditee :<br>
                    {{ $kaprodi }}
                </td>
                <td>Tanda tangan</td>
                <td>Tanggal audit : {{ $tanggalAudit }}</td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="section-label">RENCANA TINDAKAN KOREKSI (rekomendasi):</span> 
                    <span class="section-instruction">(diisi auditee setelah konsultasi dengan pimpinan dan mendapatkan persetujuan auditor)</span><br>
                    <div class="large-input-area">{{ $dokumen->output ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    Auditee:<br>
                    {{ $kaprodi }}
                </td>
                <td>Tanda tangan</td>
                <td>Tanggal : {{ \Carbon\Carbon::parse($dokumen->tanggal_pemenuhan)->locale('id')->format('j F Y') }}</td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="section-label">TINJAUAN EFEKTIFITAS KOREKSI :</span> 
                    <span class="section-instruction">(diisi oleh auditor pada audit berikutnya/audit tindak lanjut)</span><br>
                    <div class="large-input-area">{{ $dokumen->tinjauan_efektivitas_koreksi ?? '-' }}</div>
                    <br>
                    <span class="section-label">Kesimpulan :</span> 
                    <div class="input-area">{{ $dokumen->kesimpulan ?? '(status dinyatakan selesai/terbitan PTK baru) coret salah satu' }}</div>
                </td>
            </tr>
            <tr class="signature-section">
                <td width="33%">
                    Auditor Tindak Lanjut :<br>
                    Ketua : {{ $auditorKetua }}<br>
                    Anggota : {{ $auditorAnggota }}
                </td>
                <td width="33%">Tanda tangan</td>
                <td width="34%">Tanggal : {{ \Carbon\Carbon::parse($dokumen->tanggal_pemenuhan)->locale('id')->format('j F Y') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>