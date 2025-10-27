@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Status Dokumen Akreditasi</h1>
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

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('laporan-status-dokumen.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lembaga_akreditasi">Lembaga Akreditasi:</label>
                            <select name="lembaga_akreditasi" id="lembaga_akreditasi" class="form-control">
                                <option value="">Semua Lembaga Akreditasi</option>
                                @foreach ($lembagaList as $lembaga)
                                    <option value="{{ $lembaga->id }}" 
                                        {{ isset($filters['lembaga_akreditasi']) && $filters['lembaga_akreditasi'] == $lembaga->id ? 'selected' : '' }}>
                                        {{ $lembaga->nama }} ({{ $lembaga->tahun }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="jenjang">Jenjang:</label>
                            <select name="jenjang" id="jenjang" class="form-control">
                                <option value="">Semua Jenjang</option>
                                @foreach ($jenjangList as $jenjang)
                                    <option value="{{ $jenjang->id }}" 
                                        {{ isset($filters['jenjang']) && $filters['jenjang'] == $jenjang->id ? 'selected' : '' }}>
                                        {{ $jenjang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>                    
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tahun">Tahun:</label>
                            <select name="tahun" id="tahun" class="form-control">
                                <option value="">Semua Tahun</option>
                                @foreach ($tahunList as $tahun)
                                    <option value="{{ $tahun->tahun }}" 
                                        {{ isset($filters['tahun']) && $filters['tahun'] == $tahun->tahun ? 'selected' : '' }}>
                                        {{ $tahun->tahun }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prodi">Program Studi</label>
                            <input type="text" class="form-control" id="prodi_search"
                                placeholder="Ketik untuk mencari Program Studi">
                            <input type="hidden" name="prodi" id="prodi_id" value="{{ $user->prodi ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ isset($filters['status']) && $filters['status'] == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="per_page">Item Per Halaman:</label>
                            <select name="per_page" id="per_page" class="form-control">
                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="{{ route('laporan-status-dokumen.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                        <button type="button" class="btn btn-info" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button type="button" class="btn btn-danger" onclick="exportPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Hasil Laporan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>No</th>
                            <th>Lembaga Akreditasi</th>
                            <th>Tahun</th>
                            <th>Jenjang</th>
                            <th>Program Studi</th>
                            <th>Fakultas</th>
                            <th>Status</th>
                            <th>Terakhir Diupdate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($penilaian->isEmpty())
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data yang tersedia</td>
                            </tr>
                        @else
                            @php $no = ($penilaian->currentPage() - 1) * $penilaian->perPage() + 1; @endphp
                            @foreach ($penilaian as $item)
                                <tr>
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $item->kriteriaDokumen->lembagaAkreditasi->nama ?? 'N/A' }}</td>
                                    <td>{{ $item->periode_atau_tahun ?? ($item->kriteriaDokumen->periode_atau_tahun ?? 'N/A') }}
                                    </td>
                                    <td>{{ $item->kriteriaDokumen->jenjang->nama ?? 'N/A' }}</td>
                                    <td>{{ $item->prodi }}</td>
                                    <td>{{ $item->fakultas }}</td>
                                    <td>
                                        <span
                                            class="badge 
                                            @if ($item->status == \App\Models\PenilaianKriteria::STATUS_DRAFT) badge-secondary
                                            @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_PENILAIAN) badge-info
                                            @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_DIAJUKAN) badge-primary
                                            @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_DISETUJUI) badge-success
                                            @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_DITOLAK) badge-danger
                                            @elseif($item->status == \App\Models\PenilaianKriteria::STATUS_REVISI) badge-warning
                                            @else badge-secondary @endif">
                                            {{ $statusList[$item->status] ?? $item->status }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($item->last_updated)->format('d-m-Y H:i') }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $penilaian->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .ui-menu-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #e3e6f0;
        }

        .ui-menu-item:hover {
            background: #4e73df;
            color: white;
        }

        @media print {

            .navbar,
            .sidebar,
            .footer,
            .scroll-to-top,
            #sidebarToggleTop,
            .card-header,
            button,
            .card:first-of-type,
            .pagination {
                display: none !important;
            }

            .content,
            .container,
            .container-fluid {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            th {
                background-color: #4e73df !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-secondary {
                background-color: #858796 !important;
                color: white !important;
            }

            .badge-info {
                background-color: #36b9cc !important;
                color: white !important;
            }

            .badge-primary {
                background-color: #4e73df !important;
                color: white !important;
            }

            .badge-success {
                background-color: #1cc88a !important;
                color: white !important;
            }

            .badge-danger {
                background-color: #e74a3b !important;
                color: white !important;
            }

            .badge-warning {
                background-color: #f6c23e !important;
                color: white !important;
            }

            .badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            body {
                font-size: 10pt !important;
            }

            table td,
            table th {
                padding: 4px !important;
            }
        }
    </style>
    <style>
        /* Tebalkan border tabel */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
    </style>
@endpush

@push('js')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {
            // Autocomplete untuk Lembaga Akreditasi
            $('#lembaga_akreditasi_search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '{{ route('search.lembaga') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    $('#lembaga_akreditasi_id').val(ui.item.id);
                    $('#lembaga_akreditasi_search').val(ui.item.text);
                    return false;
                }
            });

            // Autocomplete untuk Jenjang
            $('#jenjang_search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '{{ route('search.jenjang') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    $('#jenjang_id').val(ui.item.id);
                    $('#jenjang_search').val(ui.item.text);
                    return false;
                }
            });

            // Autocomplete untuk Tahun
            $('#tahun_search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '{{ route('search.tahun') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    $('#tahun_id').val(ui.item.id);
                    $('#tahun_search').val(ui.item.text);
                    return false;
                }
            });

            // Autocomplete untuk Program Studi
            $('#prodi_search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '{{ route('search.prodi.status') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.text,
                                    value: item.text, // biarkan value sama dengan label
                                    id: item.id,
                                    fakultas: item.fakultas
                                };
                            }));
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    // Set nilai ID prodi ke hidden field
                    $('#prodi_id').val(ui.item.id);
                    // Set nilai text prodi ke input field
                    $('#prodi_search').val(ui.item.label);
                    // Jika ada informasi fakultas, set juga
                    if (ui.item.fakultas) {
                        $('#fakultas_text').val(ui.item.fakultas.text);
                        $('#fakultas_id').val(ui.item.fakultas.id);
                    }
                    return false; // Penting untuk mencegah default behavior
                }
            });

            // Clear values when input is cleared
            $('#prodi_search').on('blur', function() {
                // Jika input kosong setelah blur, kosongkan juga hidden field
                if (!$(this).val()) {
                    $('#prodi_id').val('');
                }
                // Jika hidden field kosong tapi input ada isinya, bersihkan input juga
                else if (!$('#prodi_id').val()) {
                    $(this).val('');
                }
            });

            // Clear values when input is cleared
            $('#lembaga_akreditasi_search, #jenjang_search, #tahun_search, #prodi_search').on('input', function() {
                var inputId = $(this).attr('id');
                var hiddenId = '';

                if (inputId === 'lembaga_akreditasi_search') hiddenId = '#lembaga_akreditasi_id';
                else if (inputId === 'jenjang_search') hiddenId = '#jenjang_id';
                else if (inputId === 'tahun_search') hiddenId = '#tahun_id';
                else if (inputId === 'prodi_search') hiddenId = '#prodi_id';

                if (!$(this).val()) {
                    $(hiddenId).val('');
                }
            });

            // Per page change
            $('#per_page').change(function() {
                $('#filterForm').submit();
            });
        });

        // Fungsi untuk export PDF dengan filter yang sama
        function exportPDF() {
            // Ambil semua parameter dari form
            var params = $('#filterForm').serialize();
            // Redirect ke route export PDF dengan parameter yang sama
            window.location.href = "{{ route('laporan-status-dokumen.exportPdf') }}?" + params;
        }
    </script>
@endpush
