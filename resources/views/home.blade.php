@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Dashboard') }}</h1>

        <!-- Filter Section -->
        <div class="d-flex align-items-center">
            <form action="{{ route('home') }}" method="GET" class="mr-3 d-flex filter-section">
                @if (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM'))
                    <div style="position: relative; min-width: 250px;">
                        <label for="prodi_search" class="sr-only">Program Studi</label>
                        <input type="text" class="form-control" id="prodi_search"
                            placeholder="Ketik untuk mencari Program Studi" value="{{ $selectedProdiText ?? '' }}">
                        <input type="hidden" name="prodi" id="prodi_id" value="{{ $selectedProdi ?? '' }}">
                    </div>
                @elseif (Auth::user()->hasActiveRole('Fakultas') && isset($prodisByFakultas) && $prodisByFakultas->count() > 0)
                    <!-- Dropdown prodi untuk user Fakultas -->
                    <div class="form-group mb-0 mr-2">
                        <label for="jadwal_id" class="sr-only">Program Studi</label>
                        <select name="jadwal_id" id="jadwal_id" class="form-control" style="min-width: 250px;">
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach ($prodisByFakultas as $jadwal)
                                <option value="{{ $jadwal->id }}" 
                                    {{ $selectedJadwalId == $jadwal->id ? 'selected' : '' }}
                                    data-prodi="{{ $jadwal->prodi }}">
                                    {{ $jadwal->prodi }} ({{ \Carbon\Carbon::parse($jadwal->tanggal_mulai)->format('d M Y') }} - {{ \Carbon\Carbon::parse($jadwal->tanggal_selesai)->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="prodi" id="prodi_id" value="{{ $selectedProdi ?? '' }}">
                    </div>
                @elseif (Auth::user()->hasActiveRole('Auditor') && isset($jadwalAuditor) && $jadwalAuditor->count() > 0)
                    <!-- Dropdown jadwal untuk Auditor -->
                    <div class="form-group mb-0 mr-2">
                        <label for="jadwal_id" class="sr-only">Jadwal AMI</label>
                        <select name="jadwal_id" id="jadwal_id" class="form-control" style="min-width: 250px;">
                            <option value="">-- Pilih Jadwal AMI --</option>
                            @foreach ($jadwalAuditor as $jadwal)
                                <option value="{{ $jadwal->id }}" 
                                    {{ $selectedJadwalId == $jadwal->id ? 'selected' : '' }}
                                    data-prodi="{{ $jadwal->prodi }}">
                                    {{ $jadwal->prodi }} ({{ \Carbon\Carbon::parse($jadwal->tanggal_mulai)->format('d M Y') }} - {{ \Carbon\Carbon::parse($jadwal->tanggal_selesai)->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="prodi" id="prodi_id" value="{{ $selectedProdi ?? '' }}">
                    </div>
                @endif
        
                <div class="form-group mb-0 ml-2">
                    <label for="periode" class="sr-only">Periode</label>
                    <select name="periode" class="form-control">
                        <option value="all" {{ $selectedPeriode == 'all' ? 'selected' : '' }}>Semua Periode</option>
                        @foreach ($periodes as $periode)
                            <option value="{{ $periode }}" {{ $selectedPeriode == $periode ? 'selected' : '' }}>
                                {{ $periode }}
                            </option>
                        @endforeach
                    </select>
                </div>
        
                <button class="btn btn-primary ml-2" type="submit">
                    <i class="fas fa-filter fa-sm"></i> Filter
                </button>
            </form>
        
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>
    </div>

    @if ($selectedProdiText || ($selectedPeriode && $selectedPeriode != 'all'))
        <div class="mb-4">
            <div class="alert alert-info">
                <strong>Filter Aktif:</strong>
                @if ($selectedProdiText)
                    <span class="badge badge-primary filter-badge">Prodi: {{ $selectedProdiText }}</span>
                @endif
                @if ($selectedPeriode && $selectedPeriode != 'all')
                    <span class="badge badge-success filter-badge">Periode: {{ $selectedPeriode }}</span>
                @endif
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-primary ml-2">Hapus Filter</a>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success border-left-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <!-- Content Row -->
    <div class="row">
        <!-- Total Dokumen Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Dokumen</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widgets['total_dokumen'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumen Diajukan Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Dokumen Diajukan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widgets['dokumen_diajukan'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumen Disetujui Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Dokumen Disetujui</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widgets['dokumen_disetujui'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumen Revisi Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Dokumen Perlu Revisi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widgets['dokumen_revisi'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($widgets['users']))
            <!-- Users Card (Hanya untuk Super Admin) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-dark shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Total Pengguna</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widgets['users'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Rata-rata Nilai Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Rata-rata Nilai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($widgets['rata_nilai'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Chart Status Dokumen -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status Dokumen</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Sebutan Nilai -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Nilai</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="nilaiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($selectedProdi || !$canFilterByProdi)
        @if (count($dokumenDetail) > 0)
            @foreach ($dokumenDetail as $kriteria => $documents)
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary">
                        <h6 class="m-0 font-weight-bold text-white">{{ $kriteria }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Element</th>
                                        <th>Indikator</th>
                                        <th>Nama Dokumen</th>
                                        <th>Capaian</th>
                                        <th>Tambahan Informasi</th>
                                        <th>Nilai</th>
                                        <th>Sebutan</th>
                                        <th>Bobot</th>
                                        <th>Tertimbang</th>
                                        <th>Nilai Auditor</th>
                                        <th>Ada/Tidak Revisi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $dokumen)
                                    <tr>
                                        <td>{{ $dokumen['kode'] }}</td>
                                        <td>{{ $dokumen['element'] }}</td>
                                        <td>{{ $dokumen['indikator'] }}</td>
                                        <td>{{ $dokumen['nama_dokumen'] }}</td>
                                        <td>{{ $dokumen['informasi'] }}</td>
                                        <td>{{ $dokumen['tambahan_informasi'] }}</td>
                                        <td>{{ $dokumen['nilai'] }}</td>
                                        <td>
                                            <span
                                                class="badge 
                                                @if ($dokumen['sebutan'] == 'Sangat Baik') badge-success
                                                @elseif($dokumen['sebutan'] == 'Baik') badge-info
                                                @elseif($dokumen['sebutan'] == 'Cukup') badge-primary
                                                @elseif($dokumen['sebutan'] == 'Kurang') badge-warning
                                                @elseif($dokumen['sebutan'] == 'Sangat Kurang') badge-danger
                                                @else badge-secondary @endif">
                                                {{ $dokumen['sebutan'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $dokumen['bobot'] }}</td>
                                        <td>{{ $dokumen['tertimbang'] }}</td>
                                        <td>{{ $dokumen['nilai_auditor'] ?? '-' }}</td>
                                        <td>
                                            @if ($dokumen['revisi'])
                                                <span class="text-danger font-weight-bold">ADA REVISI</span>
                                            @else
                                                <span class="text-success font-weight-bold">TIDAK ADA REVISI</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
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
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Silakan pilih Program Studi untuk melihat detail dokumen per kriteria
        </div>
    @endif
@endsection
@push('css')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        .ui-autocomplete {
            max-height: 300px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            z-index: 99999 !important;
            /* Tingkatkan z-index */
            width: auto !important;
            min-width: 250px !important;
            border: 1px solid #e3e6f0 !important;
            border-radius: 0.35rem !important;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
            background-color: white !important;
            position: absolute !important;
        }

        .ui-menu {
            padding: 0 !important;
            margin: 0 !important;
            list-style: none !important;
            background-color: white !important;
        }

        .ui-menu-item,
        .ui-menu-item-wrapper {
            padding: 8px 12px !important;
            cursor: pointer !important;
            border-bottom: 1px solid #e3e6f0 !important;
            display: block !important;
            color: #3a3b45 !important;
        }

        .ui-menu-item:hover,
        .ui-menu-item-wrapper:hover,
        .ui-menu-item-wrapper.ui-state-active {
            background: #4e73df !important;
            color: white !important;
        }

        .ui-helper-hidden-accessible {
            display: none !important;
        }
    </style>
@endpush
@push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {
            // Set nilai awal untuk prodi
            @if ($selectedProdiText)
                $('#prodi_search').val("{{ $selectedProdiText }}");
                $('#prodi_id').val("{{ $selectedProdi }}");
            @endif

            $('#prodi_search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '{{ route('prodi.search') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.text,
                                    value: item.text,
                                    id: item.id,
                                    fakultas: item.fakultas
                                };
                            }));
                        }
                    });
                },
                minLength: 1,
                appendTo: "body", // Tambahkan ini untuk memastikan dropdown muncul di atas elemen lain
                position: {
                    my: "left top",
                    at: "left bottom",
                    collision: "none"
                },
                open: function(event, ui) {
                    // Pastikan dropdown muncul dengan benar
                    $('.ui-autocomplete').css('width', $(this).outerWidth());
                },
                select: function(event, ui) {
                    $('#prodi_id').val(ui.item.id);
                    // Tidak perlu auto-submit, gunakan tombol filter
                }
            });
            // Chart Status
            var statusData = {!! $charts['status'] ?? '[]' !!};
            var statusCtx = document.getElementById('statusChart');

            if (statusCtx && statusData.length > 0) {
                var statusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(item => item.label),
                        datasets: [{
                            data: statusData.map(item => item.value),
                            backgroundColor: [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                                '#6f42c1', '#fd7e14', '#20c9a6', '#5a5c69', '#858796'
                            ],
                            hoverBackgroundColor: [
                                '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617',
                                '#5a31a0', '#d96b10', '#169b80', '#3a3b45', '#6e7080'
                            ],
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                            // Simpan informasi tambahan
                            fullProdi: statusData.map(item => item.fullProdi || null)
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    var index = tooltipItem.index;
                                    var dataset = data.datasets[0];
                                    var label = data.labels[index];
                                    var value = dataset.data[index];
                                    var fullProdi = dataset.fullProdi ? dataset.fullProdi[index] : null;

                                    // Tampilkan nama prodi lengkap jika tersedia
                                    if (fullProdi) {
                                        return fullProdi + ' - ' + label.split(': ')[1] + ': ' + value;
                                    }

                                    return label + ': ' + value;
                                }
                            }
                        },
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                // Agar label tidak terlalu panjang
                                generateLabels: function(chart) {
                                    var data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map(function(label, i) {
                                            var meta = chart.getDatasetMeta(0);
                                            var ds = data.datasets[0];
                                            var arc = meta.data[i];
                                            var value = ds.data[i];

                                            // Tampilkan label yang lebih pendek jika terlalu panjang
                                            var displayLabel = label;
                                            if (label.length > 25) {
                                                displayLabel = label.substr(0, 22) + '...';
                                            }

                                            return {
                                                text: displayLabel + ' (' + value + ')',
                                                fillStyle: ds.backgroundColor[i],
                                                strokeStyle: ds.backgroundColor[i],
                                                lineWidth: 0,
                                                hidden: isNaN(ds.data[i]) || meta.data[i]
                                                    .hidden,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        cutoutPercentage: 80,
                    },
                });
            } else if (statusCtx) {
                statusCtx.parentNode.innerHTML = '<div class="text-center py-4">Tidak ada data</div>';
            }

            // Chart Nilai
            var nilaiData = {!! $charts['nilai'] ?? '[]' !!};
            var nilaiCtx = document.getElementById('nilaiChart');

            if (nilaiCtx && nilaiData.length > 0) {
                var nilaiChart = new Chart(nilaiCtx, {
                    type: 'doughnut',
                    data: {
                        labels: nilaiData.map(item => item.label),
                        datasets: [{
                            data: nilaiData.map(item => item.value),
                            backgroundColor: [
                                '#1cc88a', // Sangat Baik
                                '#4e73df', // Baik
                                '#f6c23e', // Cukup
                                '#e74a3b', // Kurang
                                '#5a5c69' // Sangat Kurang
                            ],
                            hoverBackgroundColor: [
                                '#17a673',
                                '#2e59d9',
                                '#dda20a',
                                '#be2617',
                                '#3a3b45'
                            ],
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        },
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        cutoutPercentage: 80,
                    },
                });
            } else if (nilaiCtx) {
                nilaiCtx.parentNode.innerHTML = '<div class="text-center py-4">Tidak ada data</div>';
            }

            // Untuk dropdown jadwal auditor
            $('#jadwal_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const prodiValue = selectedOption.data('prodi');
                if (prodiValue) {
                    $('#prodi_id').val(prodiValue);
                } else {
                    $('#prodi_id').val('');
                }
            });

            $('#jadwal_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const prodiValue = selectedOption.data('prodi');
                if (prodiValue) {
                    $('#prodi_id').val(prodiValue);
                } else {
                    $('#prodi_id').val('');
                }
            });
        });
    </script>
@endpush
