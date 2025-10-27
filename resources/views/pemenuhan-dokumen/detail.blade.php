@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Detail Pemenuhan Dokumen {{ $headerData->lembagaAkreditasi->nama ?? '' }} -
            {{ $headerData->jenjang->nama ?? '' }} - {{ $headerData->lembagaAkreditasi->tahun }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">{{ $selectedProdi }}</small>
            @endif
        </h1>
        <div>
            <!-- Tombol Print -->
            <button onclick="window.print()" class="btn btn-info btn-sm mr-2">
                <i class="fas fa-print"></i> Print
            </button>
            
            <!-- Tombol Export PDF -->
            <a href="{{ route('pemenuhan-dokumen.exportPdf', [
                'lembagaId' => $lembagaId, 
                'jenjangId' => $jenjangId, 
                'prodi' => $selectedProdi
            ]) }}" class="btn btn-danger btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            
            <!-- Tombol Kembali -->
            <a href="{{ route('pemenuhan-dokumen.showGroup', [
                'lembagaId' => $lembagaId, 
                'jenjangId' => $jenjangId, 
                'prodi' => $selectedProdi,
                'status' => request()->query('status') ?? null,
                'year' => request()->query('year') ?? null,
                'jenjang' => request()->query('jenjang') ?? null
            ]) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-left-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (count($dokumenDetail) > 0)
        @foreach ($dokumenDetail as $kriteria => $documents)
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary">
                    <h6 class="m-0 font-weight-bold text-white">{{ $kriteria }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="bg-primary text-white">Kode</th>
                                    <th class="bg-primary text-white">Element</th>
                                    <th class="bg-primary text-white">Indikator</th>
                                    <th class="bg-primary text-white">Nama Dokumen</th>
                                    <th class="bg-primary text-white">Tambahan Informasi</th>
                                    <th class="bg-primary text-white">Nilai (Capaian)</th>
                                    <th class="bg-primary text-white">Sebutan</th>
                                    <th class="bg-primary text-white">Bobot</th>
                                    <th class="bg-primary text-white">Tertimbang</th>
                                    {{-- <th class="bg-primary text-white">Nilai Auditor</th> --}}
                                    <th class="bg-primary text-white">Dokumen</th>
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
                                                    <div>{{ $nama_dokumen }}</div>
                                                    @if($index < count($dokumen->nama_dokumens) - 1)
                                                        <hr class="my-1">
                                                    @endif
                                                @endforeach
                                            @else
                                                {{ $dokumen->nama_dokumen }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($dokumen->tambahan_informasis) && count($dokumen->tambahan_informasis) > 0)
                                                @foreach($dokumen->tambahan_informasis as $index => $tambahan_informasi)
                                                    <div>{{ $tambahan_informasi }}</div>
                                                    @if($index < count($dokumen->tambahan_informasis) - 1)
                                                        <hr class="my-1">
                                                    @endif
                                                @endforeach
                                            @else
                                                {{ $dokumen->tambahan_informasi }}
                                            @endif
                                        </td>
                                        <td>{{ $dokumen->nilai }}</td>
                                        <td>
                                            <span>{{ $dokumen->sebutan ?? '-' }}</span>
                                        </td>
                                        <td>{{ $dokumen->bobot }}</td>
                                        <td>{{ $dokumen->tertimbang }}</td>
                                        {{-- <td>{{ $dokumen->nilai_auditor ?? '-' }}</td> --}}
                                        <td>
                                            @if(isset($dokumen->files_dokumen) && count($dokumen->files_dokumen) > 0)
                                                <div class="d-print-none">
                                                    @foreach($dokumen->files_dokumen as $index => $file)
                                                        <a href="{{ asset('storage/pemenuhan_dokumen/' . $file) }}"
                                                            class="btn btn-sm btn-warning mb-1" target="_blank">
                                                            <i class="fas fa-download"></i> 
                                                            @if(isset($dokumen->nama_dokumens[$index]))
                                                                {{ $dokumen->nama_dokumens[$index] }}
                                                            @else
                                                                Lihat File
                                                            @endif
                                                        </a>
                                                        @if($index < count($dokumen->files_dokumen) - 1)
                                                            <br>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                <div class="d-none d-print-block">
                                                    Dokumen Ada
                                                </div>
                                            @elseif($dokumen->file_dokumen)
                                                <div class="d-print-none">
                                                    <a href="{{ asset('uploads/pemenuhan_dokumen/' . $dokumen->file_dokumen) }}"
                                                        class="btn btn-sm btn-warning" target="_blank">
                                                        <i class="fas fa-download"></i> Lihat File
                                                    </a>
                                                </div>
                                                <div class="d-none d-print-block">
                                                    Dokumen Ada
                                                </div>
                                            @else
                                                <span class="text-muted">Belum Ada Dokumen</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- Baris Total -->
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="5" class="text-right">Rata - Rata:</td>
                                    <td>{{ $totalByKriteria[$kriteria]['nilai'] }}</td>
                                    <td>{{ $totalByKriteria[$kriteria]['sebutan'] }}</td>
                                    <td>{{ $totalByKriteria[$kriteria]['bobot'] }}</td>
                                    <td>{{ $totalByKriteria[$kriteria]['tertimbang'] }}</td>
                                    {{-- <td>{{ $totalByKriteria[$kriteria]['nilai_auditor'] }}</td> --}}
                                    <td>-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Tidak ada data dokumen untuk kriteria yang tersedia
        </div>
    @endif
    @if (count($dokumenDetail) > 0)
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-dark">
                <h6 class="m-0 font-weight-bold text-white">Total Kumulatif</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
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
                            {{-- <tr>
                                <th width="25%" class="bg-gray-200">Nilai Auditor</th>
                                <td>{{ $totalKumulatif['nilai_auditor'] }}</td>
                            </tr> --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('css')
    <style>
        .bg-primary {
            background-color: #4e73df !important;
        }

        .text-white {
            color: white !important;
        }

        .card-header {
            background-color: #4e73df !important;
        }

        .card-header h6 {
            color: white !important;
        }

        .bg-primary {
            background-color: #4e73df !important;
        }

        .text-white {
            color: white !important;
        }

        .card-header {
            background-color: #4e73df !important;
        }

        .card-header h6 {
            color: white !important;
        }
        
        @media print {
            /* Menghilangkan elemen yang tidak perlu saat print */
            .navbar, .sidebar, .footer, .scroll-to-top, .btn, #sidebarToggleTop {
                display: none !important;
            }
            
            /* Membuat layout penuh untuk konten */
            .content, .container, .container-fluid {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Memastikan semua background warna dicetak */
            .bg-primary, .bg-dark, .bg-gray-200 {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Pastikan teks putih tetap kontras */
            .text-white {
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Pastikan seluruh tabel terlihat */
            .table-responsive {
                overflow: visible !important;
            }
            
            /* Atur ukuran font agar lebih kecil dan hemat ruang */
            body {
                font-size: 10pt !important;
            }
            
            /* Kurangi padding pada table cells */
            table td, table th {
                padding: 4px !important;
            }
            
            /* Kurangi margin pada card */
            .card {
                margin-bottom: 10px !important;
            }
            
            /* Kurangi padding pada card header dan body */
            .card-header {
                padding: 6px 12px !important;
            }
            
            .card-body {
                padding: 10px !important;
            }
            
            /* Atur page layout agar tidak memotong tabel */
            .card {
                page-break-inside: avoid !important;
            }
            
            /* Hilangkan page break setelah kriteria */
            .card + .card {
                page-break-before: avoid !important;
            }
            
            /* Atur total kumulatif agar tidak terpisah */
            .total-kumulatif {
                page-break-before: avoid !important;
            }
        }
    </style>
@endpush