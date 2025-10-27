<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Hasil dan Rencana Tindak Lanjut</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: 1px solid black;
            padding: 8px;
            vertical-align: middle;
        }
        .logo-cell {
            width: 15%;
            text-align: center;
            font-weight: bold;
            padding: 15px;
        }
        .title-cell {
            width: 60%;
            text-align: center;
        }
        .kode-cell {
            width: 25%;
            padding: 0;
        }
        .university-name {
            font-weight: bold;
            font-size: 14pt;
            text-align: center;
            margin-bottom: 5px;
        }
        .university-address {
            font-size: 11pt;
            text-align: center;
            margin-bottom: 3px;
        }
        .buku-cell {
            width: 15%;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
            font-size: 14pt;
        }
        .form-title {
            font-weight: bold;
            font-size: 14pt;
            text-align: center;
        }
        .kode-label {
            text-align: center;
            font-weight: bold;
            padding: 8px;
        }
        .kode-value {
            text-align: center;
            padding: 8px;
            border-top: 1px solid black;
        }
        .green {
            color: green;
        }
        .date-revision-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .date-revision-table td {
            padding: 8px;
            border: none;
        }
        .date-revision-row {
            border-top: 1px solid black;
            width: 100%;
            border-collapse: collapse;
        }
        .date-revision-row td {
            padding: 8px;
        }
        .date-revision-row td:first-child {
            width: 50%;
            border-right: 1px solid black;
        }
        .main-title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 10px 0;
        }
        .rekapitulasi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-top: 10px;
        }
        .rekapitulasi-table th, .rekapitulasi-table td {
            border: 1px solid black;
            padding: 6px;
            vertical-align: top;
        }
        .rekapitulasi-table th {
            font-weight: bold;
            text-align: center;
            background-color: #f2f2f2;
        }
        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td rowspan="2" class="logo-cell">
                <img src="{{ public_path('img/picture_logo.png') }}" alt="Logo Universitas PGRI Adi Buana">
            </td>
            <td rowspan="2" class="title-cell">
                <div class="university-name">UNIVERSITAS PGRI ADI BUANA Surabaya</div>
                <div class="university-address">Jl. Dukuh Menanggal XII, Surabaya, 60234</div>
                <div class="university-address">Telp. (031) 8289637, Fax. (031) 8289637</div>
            </td>
            <td class="kode-label">
                KODE
            </td>
        </tr>
        <tr>
            <td class="kode-value">
                {{ $kode }}
            </td>
        </tr>
        <tr>
            <td class="buku-cell">BUKU 5</td>
            <td class="form-title">FORMULIR REKAPITULASI HASIL DAN<br>RENCANA TINDAK LANJUT</td>
            <td style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="border-bottom: 1px solid black; border-right: 1px solid black; padding: 8px;">Tanggal : {{ $tanggal }}</td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid black; padding: 8px;">Revisi : {{ $revisi }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <div class="main-title">REKAPITULASI HASIL DAN RENCANA TINDAK LANJUT</div>
    
    <table class="rekapitulasi-table">
        <thead>
            <tr>
                <th style="width: 3%;">No.</th>
                <th style="width: 15%;">Uraian Temuan (hasil AMI)</th>
                <th style="width: 15%;">Akar penyebab masalah</th>
                <th style="width: 15%;">Rencana tindak Lanjut (rekomendasi)</th>
                <th style="width: 12%;">Out put</th>
                <th style="width: 10%;">Tanggal/waktu pemenuhan</th>
                <th style="width: 10%;">Penanggung jawab</th>
                <th style="width: 10%;">Bukti fisik</th>
                <th style="width: 10%;">Hasil verifikasi (sesuai/Tidak sesuai)</th>
            </tr>
            <tr>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
                <th>9</th>
            </tr>
        </thead>
        <tbody>
            @foreach($temuanItems as $index => $item)
            <tr>
                <td class="center">{{ $item['nomor'] }}</td>
                <td>{{ $item['uraian_temuan'] }}</td>
                <td>{{ $item['akar_penyebab'] }}</td>
                <td>{{ $item['rencana_tindak'] }}</td>
                <td>{{ $item['output'] }}</td>
                <td>{{ $item['tanggal_pemenuhan'] }}</td>
                <td>{{ $item['penanggung_jawab'] }}</td>
                <td>{{ $item['bukti_fisik'] }}</td>
                <td>{{ $item['hasil_verifikasi'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div class="center">Ketua Auditor,</div>
                    <div style="height: 60px;"></div>
                    <div class="center">{{ $auditorKetua ?? '_________________' }}</div>
                </td>
                <td style="width: 50%;">
                    <div class="center">Surabaya, {{ $tanggalAudit }}</div>
                    <div class="center">Ketua Program Studi {{ $prodi }},</div>
                    <div style="height: 60px;"></div>
                    <div class="center">{{ $kaprodi }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>