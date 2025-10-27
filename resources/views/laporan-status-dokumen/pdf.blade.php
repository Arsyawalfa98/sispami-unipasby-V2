<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Status Dokumen Akreditasi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            font-size: 18px;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #4e73df;
            color: white;
        }
        .header {
            margin-bottom: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .filter-info {
            margin-bottom: 15px;
            font-size: 11px;
        }
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            color: white;
            display: inline-block;
        }
        .badge-secondary { background-color: #858796; }
        .badge-info { background-color: #36b9cc; }
        .badge-primary { background-color: #4e73df; }
        .badge-success { background-color: #1cc88a; }
        .badge-danger { background-color: #e74a3b; }
        .badge-warning { background-color: #f6c23e; color: #000; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1>LAPORAN STATUS DOKUMEN AKREDITASI</h1>
            <p>SPMI ADI BUANA</p>
        </div>
        
        <div class="filter-info">
            <p><strong>Filter yang digunakan:</strong></p>
            <table style="width: 100%; border: none; margin-bottom: 15px;">
                <tr>
                    <td style="border: none; width: 20%;"><strong>Lembaga Akreditasi:</strong></td>
                    <td style="border: none;">{{ isset($filters['lembaga_akreditasi_text']) ? $filters['lembaga_akreditasi_text'] : 'Semua' }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Tahun:</strong></td>
                    <td style="border: none;">{{ isset($filters['tahun']) ? $filters['tahun'] : 'Semua' }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Jenjang:</strong></td>
                    <td style="border: none;">{{ isset($filters['jenjang_text']) ? $filters['jenjang_text'] : 'Semua' }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Program Studi:</strong></td>
                    <td style="border: none;">{{ isset($filters['prodi']) ? $filters['prodi'] : 'Semua' }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Status:</strong></td>
                    <td style="border: none;">{{ isset($filters['status']) ? $statusLabels[$filters['status']] : 'Semua' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Lembaga Akreditasi</th>
                <th width="10%">Tahun</th>
                <th width="10%">Jenjang</th>
                <th width="20%">Program Studi</th>
                <th width="15%">Fakultas</th>
                <th width="10%">Status</th>
                <th width="10%">Terakhir Diupdate</th>
            </tr>
        </thead>
        <tbody>
            @if($penilaian->isEmpty())
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada data yang tersedia</td>
                </tr>
            @else
                @php $no = 1; @endphp
                @foreach($penilaian as $item)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ $item->kriteriaDokumen->lembagaAkreditasi->nama ?? 'N/A' }}</td>
                        <td>{{ $item->periode_atau_tahun ?? $item->kriteriaDokumen->periode_atau_tahun ?? 'N/A' }}</td>
                        <td>{{ $item->kriteriaDokumen->jenjang->nama ?? 'N/A' }}</td>
                        <td>{{ $item->prodi }}</td>
                        <td>{{ $item->fakultas }}</td>
                        <td>
                            <div class="badge 
                                @if($item->status == \App\Models\PenilaianKriteria::STATUS_DRAFT) badge-secondary
                                @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_PENILAIAN) badge-info
                                @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_DIAJUKAN) badge-primary
                                @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_DISETUJUI) badge-success
                                @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_DITOLAK) badge-danger
                                @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_REVISI) badge-warning
                                @else badge-secondary
                                @endif">
                                {{ $statusLabels[$item->status] ?? $item->status }}
                            </div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($item->last_updated)->format('d-m-Y H:i') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</p>
        <p>SPMI ADI BUANA - Sistem Penjaminan Mutu Internal</p>
    </div>
</body>
</html>