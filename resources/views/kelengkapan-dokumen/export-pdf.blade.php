<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kelengkapan Dokumen (Checklist) - Universitas PGRI Adi Buana Surabaya</title>
    <style>
        @page {
            size: A4;
            margin: 10mm 8mm 10mm 8mm;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .main-table td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            font-size: 11px;
        }

        .inner-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }

        .inner-table td {
            border: none;
            padding: 3px 4px;
            vertical-align: middle;
            font-size: 11px;
        }

        .border-bottom {
            border-bottom: 1px solid #000 !important;
        }

        .border-right {
            border-right: 1px solid #000 !important;
        }

        .logo {
            text-align: center;
            vertical-align: middle !important;
            width: 8%;
            padding: 2px;
        }

        .logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .university-info {
            width: 70%;
            padding: 4px;
        }

        .code-section {
            width: 22%;
            padding: 2px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 13px;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .header-cell {
            line-height: 1.3;
            font-size: 11px;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 11px;
        }

        .checklist-table th,
        .checklist-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: left;
            font-size: 11px;
            word-wrap: break-word;
        }

        .col-header {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .main-header {
            background-color: #e6e6e6;
            font-weight: bold;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .info-table td {
            border: none;
            padding: 3px 2px;
            font-size: 11px;
        }

        .info-table .label {
            font-weight: bold;
            width: 30%;
            vertical-align: top;
        }

        .info-table .value {
            width: 70%;
        }

        .lokasi-cell {
            width: 25%;
            font-size: 11px;
        }

        .isian-bawah-lokasi {
            height: 30px;
            font-size: 11px;
        }

        /* Compact table spacing */
        .compact-row {
            height: 20px;
        }

        .compact-cell {
            padding: 2px 3px;
            font-size: 11px;
        }

        /* Responsive column widths for main checklist */
        .col-no {
            width: 6%;
            text-align: center;
        }

        .col-aspek {
            width: 40%;
        }

        .col-ada {
            width: 5%;
            text-align: center;
        }

        .col-tidak-ada {
            width: 7%;
            text-align: center;
        }

        .col-nama-dokumen {
            width: 25%;
        }

        .col-keterangan {
            width: 17%;
        }

        /* Print optimization */
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 11px;
            }
            
            .main-table {
                page-break-inside: avoid;
            }
            
            h2 {
                page-break-after: avoid;
            }
            
            .checklist-table {
                page-break-inside: auto;
            }
            
            .main-header {
                page-break-after: avoid;
            }
        }

        /* Text wrapping for long content */
        .wrap-text {
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        /* Smaller font for headers */
        .small-header {
            font-size: 10px;
        }

        /* Compact padding for data rows */
        .data-row td {
            padding: 2px 3px;
            line-height: 1.2;
        }

        /* Optimize space for auditee section */
        .auditee-section {
            margin-top: 5px;
        }

        .auditee-section th,
        .auditee-section td {
            padding: 3px;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <!-- Header Table -->
    <table class="main-table">
        <tr>
            <td rowspan="2" class="logo">
                <img src="{{ public_path('img/picture_logo.png') }}" alt="Logo Universitas PGRI Adi Buana">
            </td>
            <td class="header-cell university-info">
                <strong>UNIVERSITAS PGRI ADI BUANA Surabaya</strong><br>
                Jl. Dukuh Menanggal XII, Surabaya, 60234<br>
                Telp. (031) 8289637, Fax. (031) 8289637
            </td>
            <td class="text-center code-section">
                <strong>KODE</strong>
            </td>
        </tr>
        <tr>
            <td class="university-info"></td>
            <td class="text-center code-section"><strong>{{ $kode ?? '' }}</strong></td>
        </tr>
        <tr>
            <td class="text-center compact-cell"><strong>BUKU 5</strong></td>
            <td class="university-info compact-cell"><strong>FORM KELENGKAPAN DOKUMEN (CHECKLIST)</strong></td>
            <td style="padding: 0;" class="code-section">
                <table class="inner-table">
                    <tr>
                        <td style="width: 35%;" class="border-bottom border-right text-bold">
                            <strong>Tanggal</strong>
                        </td>
                        <td class="border-bottom">{{ $tanggal ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="border-right text-bold"><strong>Revisi</strong></td>
                        <td>{{ $revisi ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <h2>KELENGKAPAN DOKUMEN/CHECKLIST</h2>

    <!-- Auditee Information Table -->
    <table class="checklist-table auditee-section">
        <tr class="col-header">
            <th colspan="3"><strong>Auditee</strong></th>
            <th colspan="1"><strong>Tahap Audit</strong></th>
        </tr>
        <tr class="compact-row">
            <td colspan="3" class="compact-cell">{{ $prodiName ?? '' }}</td>
            <td colspan="1" class="compact-cell"></td>
        </tr>
        <tr class="col-header">
            <td class="lokasi-cell"><strong>Lokasi</strong></td>
            <td colspan="1"><strong>Ruang Lingkup</strong></td>
            <td colspan="2"><strong>Tanggal Audit</strong></td>
        </tr>
        <tr>
            <td class="isian-bawah-lokasi"></td>
            <td colspan="1" class="isian-bawah-lokasi"></td>
            <td colspan="2" class="isian-bawah-lokasi"></td>
        </tr>
        <tr class="compact-row">
            <td class="compact-cell"><strong>Wakil Auditee</strong></td>
            <td colspan="3" class="compact-cell"></td>
        </tr>
        <tr class="compact-row">
            <td class="compact-cell"><strong>Auditor Ketua</strong></td>
            <td colspan="3" class="compact-cell">{{ $auditorKetua ?? '' }}</td>
        </tr>
        <tr class="compact-row">
            <td class="compact-cell"><strong>Auditor Anggota</strong></td>
            <td colspan="3" class="compact-cell">{{ $auditorAnggota ?? '' }}</td>
        </tr>
    </table>

    <!-- Main Checklist Table -->
    <table class="checklist-table">
        <tr class="col-header small-header">
            <th class="col-no"><strong>No</strong></th>
            <th class="col-aspek"><strong>Aspek</strong></th>
            <th class="col-ada"><strong>Ada</strong></th>
            <th class="col-tidak-ada"><strong>Tidak ada</strong></th>
            <th class="col-nama-dokumen"><strong>Nama Dokumen</strong></th>
            <th class="col-keterangan"><strong>Keterangan</strong></th>
        </tr>
        <tr class="col-header small-header">
            <td class="text-center"><strong>1</strong></td>
            <td class="text-center"><strong>2</strong></td>
            <td class="text-center"><strong>3</strong></td>
            <td class="text-center"><strong>4</strong></td>
            <td class="text-center"><strong>5</strong></td>
            <td class="text-center"><strong>6</strong></td>
        </tr>

        @php
            $currentKode = '';
            $globalIndikatorCounter = 1;
        @endphp

        @foreach ($dokumenDetail as $kriteria => $documents)
            @foreach ($documents as $index => $dokumen)
                @php
                    $kode = $dokumen->kode ?? '';
                    $element = $dokumen->element ?? '';
                    $indikator = $dokumen->indikator ?? '';
                @endphp

                @if ($kode != $currentKode)
                    <tr class="main-header">
                        <td class="text-center"><strong>{{ $kode }}</strong></td>
                        <td class="wrap-text"><strong>{{ $element }}</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @php
                        $currentKode = $kode;
                    @endphp
                @endif

                @if (isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0)
                    @php
                        $rowspan = count($dokumen->nama_dokumens);
                    @endphp
                    @foreach ($dokumen->nama_dokumens as $docIndex => $namaDokumen)
                        <tr class="data-row">
                            @if ($docIndex === 0)
                                <td rowspan="{{ $rowspan }}" class="text-center">{{ $globalIndikatorCounter }}</td>
                                <td rowspan="{{ $rowspan }}" class="wrap-text">{{ $indikator }}</td>
                                <td rowspan="{{ $rowspan }}" class="text-center"></td>
                                <td rowspan="{{ $rowspan }}" class="text-center"></td>
                            @endif
                            <td class="wrap-text">{{ $namaDokumen }}</td>
                            <td class="wrap-text">{{ $dokumen->tambahan_informasis[$docIndex] ?? '' }}</td>
                        </tr>
                    @endforeach
                    @php
                        $globalIndikatorCounter++;
                    @endphp
                @else
                    <tr class="data-row">
                        <td class="text-center">{{ $globalIndikatorCounter++ }}</td>
                        <td class="wrap-text">{{ $indikator }}</td>
                        <td class="text-center"></td>
                        <td class="text-center"></td>
                        <td class="wrap-text">{{ $dokumen->nama_dokumen ?? '' }}</td>
                        <td class="wrap-text">{{ $dokumen->tambahan_informasi ?? '' }}</td>
                    </tr>
                @endif
            @endforeach
        @endforeach
    </table>
</body>

</html>