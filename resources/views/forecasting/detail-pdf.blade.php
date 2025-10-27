<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Forecasting - {{ $selectedProdi }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        h1 {
            font-size: 12pt;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px;
            font-size: 8pt;
            text-align: left;
        }
        th {
            background-color: #4e73df;
            color: white;
            font-weight: bold;
        }
        .kriteria-header {
            background-color: #4e73df;
            color: white;
            font-weight: bold;
            padding: 4px;
            margin-top: 10px;
            margin-bottom: 3px;
        }
        .total-row {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .kumulatif-header {
            background-color: #343a40;
            color: white;
            font-weight: bold;
            padding: 4px;
            margin-top: 10px;
            margin-bottom: 3px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 7pt;
            color: white;
        }
        .badge-success { background-color: #1cc88a; }
        .badge-info { background-color: #36b9cc; }
        .badge-warning { background-color: #f6c23e; }
        .badge-danger { background-color: #e74a3b; }
        .badge-secondary { background-color: #858796; }
        .doc-divider {
            border-top: 1px dashed #ccc;
            margin: 2px 0;
        }
        .nilai-akreditasi {
            margin-top: 10px;
            font-weight: bold;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <h1>Forecasting {{ $headerData->lembagaAkreditasi->nama ?? '' }} - 
       {{ $headerData->jenjang->nama ?? '' }} - {{ $headerData->periode_atau_tahun }}</h1>
    <div class="subtitle">{{ $selectedProdi }}</div>
    
    @if (count($dokumenDetail) > 0)
        @foreach ($dokumenDetail as $kriteria => $documents)
            <div class="kriteria-header">{{ $kriteria }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Element</th>
                        <th>Indikator</th>
                        <th>Nama Dokumen</th>
                        <th>Tambahan Informasi</th>
                        <th>Nilai</th>
                        <th>Bobot</th>
                        <th>Tertimbang</th>
                        <th>Nilai Auditor</th>
                        <th>Dokumen</th>
                        <th>Sebutan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $dokumen)
                        <tr>
                            <td>{{ $dokumen->kode }}</td>
                            <td>{{ $dokumen->element }}</td>
                            <td>{{ $dokumen->indikator }}</td>
                            <td>
                                @if(isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0)
                                    @foreach($dokumen->nama_dokumens as $index => $nama_dokumen)
                                        {{ $nama_dokumen }}
                                        @if($index < count($dokumen->nama_dokumens) - 1)
                                            <div class="doc-divider"></div>
                                        @endif
                                    @endforeach
                                @else
                                    {{ $dokumen->nama_dokumen }}
                                @endif
                            </td>
                            <td>
                                @if(isset($dokumen->tambahan_informasis) && count($dokumen->tambahan_informasis) > 0)
                                    @foreach($dokumen->tambahan_informasis as $index => $tambahan_informasi)
                                        {{ $tambahan_informasi }}
                                        @if($index < count($dokumen->tambahan_informasis) - 1)
                                            <div class="doc-divider"></div>
                                        @endif
                                    @endforeach
                                @else
                                    {{ $dokumen->tambahan_informasi }}
                                @endif
                            </td>
                            <td class="text-center">{{ $dokumen->nilai }}</td>
                            <td class="text-center">{{ $dokumen->bobot }}</td>
                            <td class="text-center">{{ $dokumen->tertimbang }}</td>
                            <td class="text-center">{{ $dokumen->nilai_auditor ?? '-' }}</td>
                            <td class="text-center">
                                @if(isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0)
                                    @foreach($dokumen->nama_dokumens as $index => $nama_dokumen)
                                        @if(isset($dokumen->files_dokumen) && isset($dokumen->files_dokumen[$index]))
                                            Ada
                                        @else
                                            Tidak Ada
                                        @endif
                                        @if($index < count($dokumen->nama_dokumens) - 1)
                                            <div class="doc-divider"></div>
                                        @endif
                                    @endforeach
                                @elseif(isset($dokumen->files_dokumen) && count($dokumen->files_dokumen) > 0)
                                    Ada
                                @elseif(isset($dokumen->file_dokumen) && $dokumen->file_dokumen)
                                    Ada
                                @else
                                    Tidak Ada
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $dokumen->sebutan ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                    <!-- Baris Total -->
                    <tr class="total-row">
                        <td colspan="5" class="text-right">Rata - Rata:</td>
                        <td class="text-center">{{ $totalByKriteria[$kriteria]['nilai'] }}</td>
                        <td class="text-center">{{ $totalByKriteria[$kriteria]['bobot'] }}</td>
                        <td class="text-center">{{ $totalByKriteria[$kriteria]['tertimbang'] }}</td>
                        <td class="text-center">{{ $totalByKriteria[$kriteria]['nilai_auditor'] }}</td>
                        <td class="text-center">-</td>
                        <td class="text-center">{{ $totalByKriteria[$kriteria]['sebutan'] }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        
        <!-- Total Kumulatif -->
        <div class="kumulatif-header">Total Kumulatif Semua Kriteria</div>
        <table>
            <tbody>
                <tr>
                    <th width="25%" class="bg-gray-200">Nilai (Capaian)</th>
                    <td>{{ $totalKumulatif['nilai'] }}</td>
                </tr>
                <tr>
                    <th width="25%" class="bg-gray-200">Bobot</th>
                    <td>{{ $totalKumulatif['bobot'] }}</td>
                </tr>
                <tr>
                    <th width="25%" class="bg-gray-200">Tertimbang</th>
                    <td>{{ $totalKumulatif['tertimbang'] }}</td>
                </tr>
                <tr>
                    <th width="25%" class="bg-gray-200">Nilai Auditor</th>
                    <td>{{ $totalKumulatif['nilai_auditor'] }}</td>
                </tr>
            </tbody>
        </table>
        <p>
            <strong>Status:</strong>
            <span
                class="badge badge-warning">
                {{ strtoupper($dokumen->status) }}
            </span>
        </p>
        <div class="nilai-akreditasi">
            Nilai Akreditasi = 
        </div>
    @else
        <p>Tidak ada data dokumen untuk kriteria yang tersedia</p>
    @endif
</body>
</html>