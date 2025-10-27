@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Visualisasi Forecasting {{ $headerData->lembagaAkreditasi->nama ?? '' }}
            {{ $headerData->jenjang->nama ?? '' }}
            {{ $headerData->periode_atau_tahun ?? '' }}
        </h1>
        <div>
            <a href="{{ route('forecasting.showGroup', [
                'lembagaId' => $lembagaId, 
                'jenjangId' => $jenjangId, 
                'prodi' => $selectedProdi,
                'status' => request()->query('status') ?? null,
                'year' => request()->query('year') ?? null,
                'jenjang' => request()->query('jenjang') ?? null
            ]) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-info btn-sm ml-2">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Nama Prodi: {{ $selectedProdi }}</h6>
        </div>
        <div class="card-body">
            <h6 class="font-weight-bold">Tahun Pengukuran Mutu: {{ $headerData->periode_atau_tahun ?? date('Y') }}</h6>
        </div>
    </div>

    <!-- Program Studi Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-graduation-cap mr-2"></i>Informasi Program Studi
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="font-weight-bold text-primary">Nama Program Studi:</h6>
                    <p class="text-gray-800 mb-3">{{ $selectedProdi }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold text-primary">Tahun Pengukuran Mutu:</h6>
                    <p class="text-gray-800 mb-3">{{ $headerData->periode_atau_tahun ?? date('Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Ringkasan Kriteria -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Ringkasan Nilai Kriteria</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="bg-primary text-white" width="60%">Kriteria</th>
                            <th class="bg-primary text-white" width="20%">Nilai per Standar</th>
                            <th class="bg-primary text-white" width="20%">Sebutan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalRataRata = 0; $criteriaCount = 0; @endphp
                        @foreach($totalByKriteria as $kriteria => $data)
                            <tr>
                                <td>{{ $kriteria }}</td>
                                <td class="text-center">{{ number_format($data['nilai'], 2) }}</td>
                                <td class="text-center">
                                    <span class="badge 
                                        @if($data['sebutan'] == 'Sangat Baik') badge-success
                                        @elseif($data['sebutan'] == 'Baik') badge-info
                                        @elseif($data['sebutan'] == 'Cukup') badge-primary
                                        @elseif($data['sebutan'] == 'Kurang') badge-warning
                                        @elseif($data['sebutan'] == 'Sangat Kurang') badge-danger
                                        @else badge-secondary @endif">
                                        {{ $data['sebutan'] }}
                                    </span>
                                </td>
                            </tr>
                            @php 
                                $totalRataRata += $data['nilai']; 
                                $criteriaCount++; 
                            @endphp
                        @endforeach
                        <tr class="bg-light font-weight-bold">
                            <td>Rata-rata</td>
                            <td class="text-center">
                                @php 
                                    $avgRataRata = $criteriaCount > 0 ? $totalRataRata / $criteriaCount : 0;
                                    $sebutanRataRata = '';
                                    if ($avgRataRata == 4) $sebutanRataRata = 'Sangat Baik';
                                    elseif ($avgRataRata >= 3 && $avgRataRata < 4) $sebutanRataRata = 'Baik';
                                    elseif ($avgRataRata >= 2 && $avgRataRata < 3) $sebutanRataRata = 'Cukup';
                                    elseif ($avgRataRata >= 1 && $avgRataRata < 2) $sebutanRataRata = 'Kurang';
                                    elseif ($avgRataRata >= 0 && $avgRataRata < 1) $sebutanRataRata = 'Sangat Kurang';
                                    else $sebutanRataRata = '-';
                                @endphp
                                {{ number_format($avgRataRata, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge 
                                    @if($sebutanRataRata == 'Sangat Baik') badge-success
                                    @elseif($sebutanRataRata == 'Baik') badge-info
                                    @elseif($sebutanRataRata == 'Cukup') badge-primary
                                    @elseif($sebutanRataRata == 'Kurang') badge-warning
                                    @elseif($sebutanRataRata == 'Sangat Kurang') badge-danger
                                    @else badge-secondary @endif">
                                    {{ $sebutanRataRata }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Grafik Overview -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-chart-pie mr-2"></i>Grafik Overview Semua Kriteria
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="font-weight-bold mb-3 text-center">Perbandingan Nilai Antar Kriteria</h6>
                    <div style="height: 500px;">
                        <canvas id="overview-bar-chart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold mb-3 text-center">Radar Chart Semua Kriteria</h6>
                    <div style="height: 500px;">
                        <canvas id="overview-radar-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Kriteria dengan Grafik - Compact Version -->
    @foreach($dokumenDetail as $kriteria => $documents)
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary">
                <h6 class="m-0 font-weight-bold text-white">{{ $kriteria }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="bg-primary text-white">Kode</th>
                                        <th class="bg-primary text-white">Nilai (Capaian)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $dokumen)
                                        <tr>
                                            <td>{{ $dokumen->kode }}</td>
                                            <td class="text-center">{{ $dokumen->nilai }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-light font-weight-bold">
                                        <td>Rata-rata</td>
                                        <td class="text-center">{{ number_format($totalByKriteria[$kriteria]['nilai'], 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:200px; width:100%">
                            <canvas id="chart-{{ str_replace(' ', '-', $kriteria) }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Summary Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-dark">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-calculator mr-2"></i>Kesimpulan Penilaian
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="font-weight-bold text-primary mb-3">Hasil Penilaian Akreditasi</h5>
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%" class="font-weight-bold">Program Studi:</td>
                                <td>{{ $selectedProdi }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Tahun Penilaian:</td>
                                <td>{{ $headerData->periode_atau_tahun ?? date('Y') }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Lembaga Akreditasi:</td>
                                <td>{{ $headerData->lembagaAkreditasi->nama ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Jenjang:</td>
                                <td>{{ $headerData->jenjang->nama ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Nilai Rata-rata:</td>
                                <td>
                                    <span class="badge badge-primary" style="font-size: 1.1rem;">
                                        {{ number_format($avgRataRata, 2) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Sebutan:</td>
                                <td>
                                    <span class="badge 
                                        @if($sebutanRataRata == 'Sangat Baik') badge-success
                                        @elseif($sebutanRataRata == 'Baik') badge-info
                                        @elseif($sebutanRataRata == 'Cukup') badge-primary
                                        @elseif($sebutanRataRata == 'Kurang') badge-warning
                                        @elseif($sebutanRataRata == 'Sangat Kurang') badge-danger
                                        @else badge-secondary @endif" 
                                        style="font-size: 1.1rem;">
                                        {{ $sebutanRataRata }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        /* SB Admin 2 Compatible Styles */
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
        .badge-success {
            background-color: #1cc88a;
        }
        .badge-info {
            background-color: #36b9cc;
        }
        .badge-primary {
            background-color: #4e73df;
        }
        .badge-warning {
            background-color: #f6c23e;
        }
        .badge-danger {
            background-color: #e74a3b;
        }
        .badge-secondary {
            background-color: #858796;
        }
        .bg-success {
            background-color: #1cc88a !important;
        }
        .bg-dark {
            background-color: #5a5c69 !important;
        }
        /* Compact Layout - Controlled Heights */
        .chart-container {
            margin: 0 auto;
            max-height: 200px;
        }
        
        /* Force chart containers to not exceed specified heights for individual charts only */
        .chart-container canvas {
            max-height: 200px !important;
        }
        
        /* Allow overview charts to be larger */
        #overview-bar-chart, #overview-radar-chart {
            max-height: 500px !important;
        }
        
        /* Compact card spacing */
        .card {
            margin-bottom: 1rem !important;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        /* Compact Table Styling */
        .table {
            margin-bottom: 0;
        }
        .table td, .table th {
            padding: 0.5rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
        }
        .table-bordered {
            border: 1px solid #e3e6f0;
        }
        .table-bordered td, .table-bordered th {
            border: 1px solid #e3e6f0;
        }
        
        /* Card Styling - SB Admin 2 */
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
            border: 1px solid #e3e6f0;
        }
        
        @media print {
            .navbar, .sidebar, .footer, .scroll-to-top, .btn, #sidebarToggleTop {
                display: none !important;
            }
            
            .content, .container, .container-fluid {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .bg-primary {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .text-white {
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .table-responsive {
                overflow: visible !important;
            }
            
            .card {
                page-break-inside: avoid !important;
                margin-bottom: 10px !important;
            }
            
            .chart-container {
                height: 150px !important;
            }
            
            .chart-container canvas {
                max-height: 150px !important;
            }
            
            /* Keep overview charts readable in print */
            #overview-bar-chart, #overview-radar-chart {
                max-height: 300px !important;
            }
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/chart.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data untuk grafik overview
            const kriteriaLabels = {!! json_encode(array_keys($totalByKriteria)) !!};
            const kriteriaValues = {!! json_encode(array_values(array_column($totalByKriteria, 'nilai'))) !!};
            const kriteriaSebutans = {!! json_encode(array_values(array_column($totalByKriteria, 'sebutan'))) !!};
            
            // Warna berdasarkan sebutan
            function getColorBySebutan(sebutan) {
                switch(sebutan) {
                    case 'Sangat Baik': return '#1cc88a';
                    case 'Baik': return '#36b9cc';
                    case 'Cukup': return '#4e73df';
                    case 'Kurang': return '#f6c23e';
                    case 'Sangat Kurang': return '#e74a3b';
                    default: return '#858796';
                }
            }

            // Bar Chart Overview
            const barCtx = document.getElementById('overview-bar-chart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: kriteriaLabels,
                    datasets: [{
                        label: 'Nilai per Standar',
                        data: kriteriaValues,
                        backgroundColor: kriteriaSebutans.map(sebutan => getColorBySebutan(sebutan)),
                        borderColor: kriteriaSebutans.map(sebutan => getColorBySebutan(sebutan)),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 4,
                            ticks: {
                                stepSize: 0.5
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Perbandingan Nilai Antar Kriteria'
                        }
                    }
                }
            });

            // Radar Chart Overview
            const radarCtx = document.getElementById('overview-radar-chart').getContext('2d');
            new Chart(radarCtx, {
                type: 'radar',
                data: {
                    labels: kriteriaLabels,
                    datasets: [{
                        label: 'Nilai Capaian',
                        data: kriteriaValues,
                        backgroundColor: 'rgba(78, 115, 223, 0.2)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    elements: {
                        line: {
                            borderWidth: 3
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 4,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Radar Chart Semua Kriteria'
                        }
                    }
                }
            });

            // Individual Criteria Charts
            @foreach($chartData as $kriteria => $data)
                var ctx = document.getElementById('chart-{{ str_replace(' ', '-', $kriteria) }}').getContext('2d');
                
                var chartData = {
                    labels: {!! json_encode($data['labels']) !!},
                    datasets: [{
                        label: 'Nilai Capaian',
                        data: {!! json_encode($data['series']) !!},
                        backgroundColor: 'rgba(78, 115, 223, 0.3)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)'
                    }]
                };
                
                var radarChart = new Chart(ctx, {
                    type: 'radar',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        elements: {
                            line: {
                                borderWidth: 3
                            }
                        },
                        scales: {
                            r: {
                                angleLines: {
                                    display: true
                                },
                                suggestedMin: 0,
                                suggestedMax: 4,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            @endforeach
        });
    </script>
@endpush