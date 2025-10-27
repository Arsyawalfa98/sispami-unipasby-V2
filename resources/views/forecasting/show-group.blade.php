@extends('layouts.admin')
@php
    use App\Models\PenilaianKriteria;
@endphp

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Forecasting {{ $headerData->lembagaAkreditasi->nama ?? '' }}
            {{ $headerData->jenjang->nama ?? '' }}
            {{ $headerData->periode_atau_tahun ?? '' }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">{{ $selectedProdi }}</small>
            @endif
        </h1>
        <a href="{{ route('forecasting.index', array_filter([
            'status' => $filterParams['status'] ?? null,
            'year' => $filterParams['year'] ?? null,
            'jenjang' => $filterParams['jenjang'] ?? null,
            'search' => request()->query('search') ?? null,
        ])) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Filter Info Card -->
    @if (isset($filterParams) && count(array_filter($filterParams)) > 0)
        <div class="card filter-card border-0 mb-3">
            <div class="card-body py-2">
                <div class="filter-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-filter text-primary"></i> Data difilter berdasarkan:</strong>
                    <a href="{{ route('forecasting.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Hapus Filter
                    </a>
                </div>
                <div class="mt-2">
                    @foreach($filterParams as $key => $value)
                        @if(!empty($value) && $value !== 'all')
                            <span class="badge badge-primary mr-2">
                                <i class="fas fa-{{ $key == 'status' ? 'tag' : ($key == 'year' ? 'calendar-alt' : 'graduation-cap') }}"></i>
                                {{ ucfirst($key) }}: {{ $value }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Program Studi Selection Card -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Pilih Program Studi</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('forecasting.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prodi" class="font-weight-bold">Program Studi:</label>
                            <select name="prodi" id="prodi" class="form-control" onchange="this.form.submit();">
                                <option value="">Pilih Program Studi</option>
                                @foreach ($prodiList as $prodi)
                                    <option value="{{ $prodi['prodi'] }}"
                                        {{ $selectedProdi == $prodi['prodi'] ? 'selected' : '' }}
                                        data-status="{{ $prodi['status'] }}"
                                        data-ketua="{{ $prodi['tim_auditor_detail']['ketua'] ?? '' }}"
                                        data-anggota="{{ isset($prodi['tim_auditor_detail']['anggota']) ? implode('|', $prodi['tim_auditor_detail']['anggota']) : '' }}"
                                        data-tim-auditor="{{ $prodi['tim_auditor'] ?? '' }}">
                                        {{ $prodi['prodi_dengan_tahun'] ?? $prodi['prodi'] . ' - ' . ($headerData->periode_atau_tahun ?? date('Y')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Hidden inputs untuk mempertahankan filter -->
                        @foreach($filterParams as $key => $value)
                            @if(!empty($value) && $value !== 'all')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <!-- Program Studi Info Panel -->
                        <div id="prodi-info-panel" class="h-100" style="{{ $selectedProdi ? '' : 'display: none;' }}">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="font-weight-bold text-primary mb-0" id="prodi-name">
                                            {{ $selectedProdi ?? 'Program Studi' }}
                                        </h6>
                                        <span id="prodi-status" class="badge badge-secondary">Status</span>
                                    </div>

                                    <!-- Tim Auditor Section -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-users text-primary mr-2"></i>
                                            <strong class="text-gray-800">Tim Auditor:</strong>
                                        </div>
                                        <div id="auditor-list" class="ml-4">
                                            @if ($selectedProdi && isset($currentProdiData['tim_auditor_detail']))
                                                @if ($currentProdiData['tim_auditor_detail']['ketua'])
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fas fa-crown text-warning mr-2"></i>
                                                        <strong>{{ $currentProdiData['tim_auditor_detail']['ketua'] }}</strong>
                                                        <small class="text-muted ml-1">(Ketua)</small>
                                                    </div>
                                                @endif
                                                @if (!empty($currentProdiData['tim_auditor_detail']['anggota']))
                                                    @foreach ($currentProdiData['tim_auditor_detail']['anggota'] as $anggota)
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="fas fa-user text-secondary mr-2"></i>
                                                            {{ $anggota }}
                                                            <small class="text-muted ml-1">(Anggota)</small>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @elseif($selectedProdi && isset($currentProdiData['tim_auditor']))
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user text-secondary mr-2"></i>
                                                    {{ $currentProdiData['tim_auditor'] }}
                                                </div>
                                            @else
                                                <small class="text-muted">Pilih program studi untuk melihat tim auditor</small>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($selectedProdi)
                                        <!-- Action Buttons -->
                                        <div class="text-center">
                                            <div class="mb-2">
                                                <strong class="small text-gray-700">Status Penilaian:</strong><br>
                                                <span class="badge badge-{{ $penilaianStatus != PenilaianKriteria::STATUS_DRAFT ? 'info' : 'warning' }}">
                                                    {{ strtoupper($penilaianStatus) }}
                                                </span>
                                            </div>

                                            <div class="btn-group-vertical w-100">
                                                <button onclick="window.print()" class="btn btn-info btn-sm mb-1">
                                                    <i class="fas fa-print"></i> Print
                                                </button>
                                                <a href="{{ route('forecasting.exportPdf', array_merge([
                                                    'lembagaId' => $lembagaId,
                                                    'jenjangId' => $jenjangId,
                                                    'prodi' => $selectedProdi,
                                                ], array_filter($filterParams))) }}" 
                                                   class="btn btn-danger btn-sm mb-1" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Export PDF
                                                </a>
                                                <a href="{{ route('forecasting.visualize', array_merge([
                                                    'lembagaId' => $lembagaId,
                                                    'jenjangId' => $jenjangId,
                                                    'prodi' => $selectedProdi,
                                                ], array_filter($filterParams))) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-chart-line"></i> Visualisasi Data
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($selectedProdi)
        @if (isset($dokumenDetail) && count($dokumenDetail) > 0)
            <!-- Kriteria Details -->
            @foreach ($dokumenDetail as $kriteria => $documents)
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary">
                        <h6 class="m-0 font-weight-bold text-white">{{ $kriteria }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-criteria">
                                <thead>
                                    <tr>
                                        <th class="bg-primary text-white text-center" width="8%">Kode</th>
                                        <th class="bg-primary text-white" width="20%">Element</th>
                                        <th class="bg-primary text-white" width="25%">Indikator</th>
                                        <th class="bg-primary text-white" width="15%">Nama Dokumen</th>
                                        <th class="bg-primary text-white" width="12%">Tambahan Informasi</th>
                                        <th class="bg-primary text-white text-center" width="6%">Nilai</th>
                                        <th class="bg-primary text-white text-center" width="6%">Bobot</th>
                                        <th class="bg-primary text-white text-center" width="6%">Tertimbang</th>
                                        <th class="bg-primary text-white text-center" width="8%">Dokumen</th>
                                        <th class="bg-primary text-white text-center" width="10%">Sebutan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $dokumen)
                                        <tr>
                                            <td class="text-center font-weight-bold">{{ $dokumen->kode }}</td>
                                            <td>{{ $dokumen->element }}</td>
                                            <td>{{ $dokumen->indikator }}</td>
                                            <td>
                                                @if (isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0)
                                                    @foreach ($dokumen->nama_dokumens as $index => $nama_dokumen)
                                                        <div class="small">{{ $nama_dokumen }}</div>
                                                        @if ($index < count($dokumen->nama_dokumens) - 1)
                                                            <hr class="my-1">
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">{{ $dokumen->nama_dokumen ?: 'Tidak ada dokumen' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($dokumen->tambahan_informasis) && count($dokumen->tambahan_informasis) > 0)
                                                    @foreach ($dokumen->tambahan_informasis as $index => $tambahan_informasi)
                                                        <div class="small">{{ $tambahan_informasi }}</div>
                                                        @if ($index < count($dokumen->tambahan_informasis) - 1)
                                                            <hr class="my-1">
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">{{ $dokumen->tambahan_informasi ?: '-' }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center font-weight-bold">
                                                <span class="badge badge-{{ $dokumen->nilai >= 3 ? 'success' : ($dokumen->nilai >= 2 ? 'warning' : 'danger') }}">
                                                    {{ number_format($dokumen->nilai, 2) }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ $dokumen->bobot }}</td>
                                            <td class="text-center">{{ number_format($dokumen->tertimbang, 2) }}</td>
                                            <td class="text-center">
                                                @php
                                                    $hasDoc = false;
                                                    if (isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0) {
                                                        foreach ($dokumen->nama_dokumens as $index => $nama_dokumen) {
                                                            if (isset($dokumen->files_dokumen) && isset($dokumen->files_dokumen[$index])) {
                                                                $hasDoc = true;
                                                                break;
                                                            }
                                                        }
                                                    } elseif (isset($dokumen->files_dokumen) && count($dokumen->files_dokumen) > 0) {
                                                        $hasDoc = true;
                                                    } elseif (isset($dokumen->file_dokumen) && $dokumen->file_dokumen) {
                                                        $hasDoc = true;
                                                    }
                                                @endphp
                                                <span class="badge badge-{{ $hasDoc ? 'success' : 'danger' }}">
                                                    {{ $hasDoc ? 'Ada' : 'Tidak Ada' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $sebutanClass = 'secondary';
                                                    switch($dokumen->sebutan) {
                                                        case 'Sangat Baik': $sebutanClass = 'success'; break;
                                                        case 'Baik': $sebutanClass = 'info'; break;
                                                        case 'Cukup': $sebutanClass = 'primary'; break;
                                                        case 'Kurang': $sebutanClass = 'warning'; break;
                                                        case 'Sangat Kurang': $sebutanClass = 'danger'; break;
                                                    }
                                                @endphp
                                                <span class="badge badge-{{ $sebutanClass }}">
                                                    {{ $dokumen->sebutan ?? '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    <!-- Summary Row -->
                                    @if (isset($totalByKriteria[$kriteria]))
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="5" class="text-right">Rata-rata:</td>
                                            <td class="text-center">
                                                <span class="badge badge-dark">
                                                    {{ number_format($totalByKriteria[$kriteria]['nilai'], 2) }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ $totalByKriteria[$kriteria]['bobot'] }}</td>
                                            <td class="text-center">{{ number_format($totalByKriteria[$kriteria]['tertimbang'], 2) }}</td>
                                            <td class="text-center">-</td>
                                            <td class="text-center">
                                                @php
                                                    $sebutanClass = 'secondary';
                                                    switch($totalByKriteria[$kriteria]['sebutan']) {
                                                        case 'Sangat Baik': $sebutanClass = 'success'; break;
                                                        case 'Baik': $sebutanClass = 'info'; break;
                                                        case 'Cukup': $sebutanClass = 'primary'; break;
                                                        case 'Kurang': $sebutanClass = 'warning'; break;
                                                        case 'Sangat Kurang': $sebutanClass = 'danger'; break;
                                                    }
                                                @endphp
                                                <span class="badge badge-{{ $sebutanClass }}">
                                                    {{ $totalByKriteria[$kriteria]['sebutan'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Total Kumulatif -->
            @if (isset($totalKumulatif))
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-dark">
                        <h6 class="m-0 font-weight-bold text-white">Total Kumulatif Semua Kriteria</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-summary">
                                        <tbody>
                                            <tr>
                                                <th width="30%" class="bg-light">Total Nilai (Capaian)</th>
                                                <td class="font-weight-bold text-primary">{{ number_format($totalKumulatif['nilai'], 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Total Bobot</th>
                                                <td>{{ $totalKumulatif['bobot'] }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Total Tertimbang</th>
                                                <td>{{ number_format($totalKumulatif['tertimbang'], 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h5 class="text-dark mb-3">Status Penilaian</h5>
                                    <span class="badge badge-lg badge-{{ $penilaianStatus != PenilaianKriteria::STATUS_DRAFT ? 'info' : 'warning' }}" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                        {{ strtoupper($penilaianStatus) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Nilai Akreditasi Section -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="font-weight-bold text-primary mb-3">
                                        <i class="fas fa-award mr-2"></i>Nilai Akreditasi
                                    </h5>
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold text-muted">Nilai Akreditasi:</label>
                                                        <div class="border rounded p-3 bg-light text-center" style="min-height: 60px;">
                                                            <span class="text-muted">
                                                                <i class="fas fa-calculator mr-2"></i>
                                                                Belum dihitung
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold text-muted">Predikat:</label>
                                                        <div class="border rounded p-3 bg-light text-center" style="min-height: 60px;">
                                                            <span class="text-muted">
                                                                <i class="fas fa-medal mr-2"></i>
                                                                Belum ditentukan
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-3">
                                                <div class="col-md-12" hidden>
                                                    <div class="form-group mb-0">
                                                        <label class="font-weight-bold text-muted">Keterangan:</label>
                                                        <div class="border rounded p-3 bg-light" style="min-height: 80px;">
                                                            <span class="text-muted">
                                                                <i class="fas fa-clipboard-list mr-2"></i>
                                                                Keterangan tambahan akan ditampilkan di sini
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>Tidak ada data dokumen untuk kriteria yang tersedia
            </div>
        @endif
    @else
        <div class="alert alert-info">
            <i class="fas fa-hand-point-up mr-2"></i>Silakan pilih Program Studi terlebih dahulu untuk melihat detail forecasting.
        </div>
    @endif

@endsection

@push('css')
    <style>
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }

        .filter-card .badge {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .badge-lg {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        /* Enhanced Table Styling */
        .table-criteria {
            border: 2px solid #4e73df !important;
            margin-bottom: 0;
        }

        .table-criteria th {
            background-color: #4e73df !important;
            color: white !important;
            border: 1px solid #3d5ac7 !important;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 12px 8px;
            vertical-align: middle;
        }

        .table-criteria td {
            border: 1px solid #dee2e6 !important;
            vertical-align: middle;
            font-size: 0.875rem;
            padding: 10px 8px;
            background-color: #fff;
        }

        .table-criteria tbody tr:nth-child(odd) {
            background-color: #f8f9fc;
        }

        .table-criteria tbody tr:hover {
            background-color: #eaecf4;
        }

        .table-criteria .bg-light {
            background-color: #e3e6f0 !important;
            font-weight: bold;
        }

        .table-summary {
            border: 2px solid #5a5c69 !important;
        }

        .table-summary th,
        .table-summary td {
            border: 1px solid #dee2e6 !important;
            padding: 12px;
            vertical-align: middle;
        }

        .table-summary th {
            background-color: #f8f9fc !important;
            font-weight: 600;
        }

        .small {
            font-size: 0.8rem;
        }

        /* Card styling improvements */
        .card {
            border: 1px solid #e3e6f0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        .card-header {
            border-bottom: 1px solid #e3e6f0;
        }

        /* Progress bar styling */
        .progress {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
        }

        @media print {
            .navbar, .sidebar, .footer, .scroll-to-top, .btn, #sidebarToggleTop,
            .filter-card, .alert {
                display: none !important;
            }
            
            .content, .container, .container-fluid {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .bg-primary, .bg-dark, .bg-light {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-criteria th {
                background-color: #4e73df !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .card {
                page-break-inside: avoid !important;
                margin-bottom: 15px !important;
                border: 1px solid #000 !important;
            }
            
            .table-criteria,
            .table-summary {
                border: 1px solid #000 !important;
            }

            .table-criteria th,
            .table-criteria td,
            .table-summary th,
            .table-summary td {
                border: 1px solid #000 !important;
            }
            
            .table {
                font-size: 10pt !important;
            }
            
            .table th, .table td {
                padding: 4px !important;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        function showProdiInfo(value) {
            const panel = document.getElementById('prodi-info-panel');
            
            if (!value) {
                panel.style.display = 'none';
                return;
            }

            const selectedOption = document.querySelector(`option[value="${value}"]`);
            if (!selectedOption) return;

            const status = selectedOption.dataset.status;
            const ketua = selectedOption.dataset.ketua;
            const anggota = selectedOption.dataset.anggota;
            const timAuditor = selectedOption.dataset.timAuditor;

            // Update panel content
            document.getElementById('prodi-name').textContent = selectedOption.text;

            // Update status badge
            const statusBadge = document.getElementById('prodi-status');
            statusBadge.textContent = status;

            // Determine badge class based on status
            let badgeClass = 'badge-secondary';
            if (status === 'Sedang Berlangsung') badgeClass = 'badge-success';
            else if (status === 'Selesai') badgeClass = 'badge-info';
            else if (status === 'Belum Dimulai') badgeClass = 'badge-warning';

            statusBadge.className = `badge ${badgeClass}`;

            // Update auditor list
            const auditorList = document.getElementById('auditor-list');
            auditorList.innerHTML = '';

            if (ketua) {
                auditorList.innerHTML += `
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-crown text-warning mr-2"></i>
                        <strong>${ketua}</strong>
                        <small class="text-muted ml-1">(Ketua)</small>
                    </div>
                `;
            }

            if (anggota) {
                const anggotaList = anggota.split('|');
                anggotaList.forEach(nama => {
                    if (nama.trim()) {
                        auditorList.innerHTML += `
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-user text-secondary mr-2"></i>
                                ${nama.trim()}
                                <small class="text-muted ml-1">(Anggota)</small>
                            </div>
                        `;
                    }
                });
            }

            if (timAuditor && !ketua && !anggota) {
                auditorList.innerHTML += `
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-user text-secondary mr-2"></i>
                        ${timAuditor}
                    </div>
                `;
            }

            if (!ketua && !anggota && !timAuditor) {
                auditorList.innerHTML = '<small class="text-muted">Belum ada tim auditor</small>';
            }

            panel.style.display = 'block';
        }

        // Initialize info panel if prodi is already selected
        document.addEventListener('DOMContentLoaded', function() {
            const prodiSelect = document.getElementById('prodi');
            if (prodiSelect.value) {
                showProdiInfo(prodiSelect.value);
            }
        });
    </script>
@endpush