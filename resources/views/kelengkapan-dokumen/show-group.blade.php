{{-- resources/views/kelengkapan-dokumen/show-group.blade.php --}}
@extends('layouts.admin')
@php
    use App\Models\PenilaianKriteria;
    use App\Services\PemenuhanDokumen\JadwalService;
@endphp
@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Form Kelengkapan Dokumen {{ $headerData->lembagaAkreditasi->nama ?? '' }}
            {{ $headerData->jenjang->nama ?? '' }}
            {{ $headerData->periode_atau_tahun ?? '' }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">{{ $selectedProdi }}</small>
            @endif
        </h1>
        <a href="{{ route('kelengkapan-dokumen.index', [
            'status' => $filterParams['status'] ?? null,
            'year' => $filterParams['year'] ?? null,
            'jenjang' => $filterParams['jenjang'] ?? null,
            'search' => request()->query('search') ?? null,
        ]) }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Pilih Program Studi</h6>
        </div>
        <div class="card-body">
            @if (isset($filterParams) && count(array_filter($filterParams)) > 0)
                <div class="alert alert-info">
                    <strong><i class="fas fa-filter"></i> Data difilter berdasarkan:</strong>
                    @if (!empty($filterParams['status']) && $filterParams['status'] !== 'all')
                        <span class="badge badge-primary">Status: {{ $filterParams['status'] }}</span>
                    @endif
                    @if (!empty($filterParams['year']) && $filterParams['year'] !== 'all')
                        <span class="badge badge-primary">Tahun: {{ $filterParams['year'] }}</span>
                    @endif
                    @if (!empty($filterParams['jenjang']) && $filterParams['jenjang'] !== 'all')
                        <span class="badge badge-primary">Jenjang: {{ $filterParams['jenjang'] }}</span>
                    @endif
                    <a href="{{ route('kelengkapan-dokumen.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                        class="btn btn-sm btn-outline-secondary float-right">
                        <i class="fas fa-times"></i> Hapus Filter
                    </a>
                </div>
            @endif

            <!-- Form pemilihan prodi -->
            <form method="GET"
                action="{{ route('kelengkapan-dokumen.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                class="mb-0">
                <div class="row align-items-start">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label for="prodi">Program Studi:</label>
                            <select name="prodi" id="prodi" class="form-control"
                                onchange="showProdiInfo(this.value); this.form.submit();">
                                <option value="">Pilih Program Studi</option>
                                @foreach ($prodiList as $prodi)
                                    <option value="{{ $prodi['prodi'] }}"
                                        {{ $selectedProdi == $prodi['prodi'] ? 'selected' : '' }}
                                        data-status="{{ $prodi['status'] }}"
                                        data-ketua="{{ isset($prodi['tim_auditor_detail']['ketua']) ? $prodi['tim_auditor_detail']['ketua'] : '' }}"
                                        data-anggota="{{ isset($prodi['tim_auditor_detail']['anggota']) ? implode('|', $prodi['tim_auditor_detail']['anggota']) : '' }}"
                                        data-tim-auditor="{{ isset($prodi['tim_auditor']) ? $prodi['tim_auditor'] : '' }}">
                                        {{ $prodi['prodi_dengan_tahun'] ?? $prodi['prodi'] . ' - ' . ($headerData->periode_atau_tahun ?? date('Y')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Hidden inputs untuk mempertahankan filter -->
                        @if (!empty($filterParams['status']) && $filterParams['status'] !== 'all')
                            <input type="hidden" name="status" value="{{ $filterParams['status'] }}">
                        @endif
                        @if (!empty($filterParams['year']) && $filterParams['year'] !== 'all')
                            <input type="hidden" name="year" value="{{ $filterParams['year'] }}">
                        @endif
                        @if (!empty($filterParams['jenjang']) && $filterParams['jenjang'] !== 'all')
                            <input type="hidden" name="jenjang" value="{{ $filterParams['jenjang'] }}">
                        @endif
                    </div>

                    <div class="col-md-6">
                        <!-- Info Panel -->
                        <div id="prodi-info-panel" style="{{ $selectedProdi ? '' : 'display: none;' }}">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="font-weight-bold text-primary mb-0" id="prodi-name">
                                            {{ $selectedProdi ?? 'Program Studi' }}
                                        </h6>
                                        <span id="prodi-status" class="badge badge-secondary">Status</span>
                                    </div>

                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-users text-primary mr-2"></i>
                                            <strong class="text-gray-800">Tim Auditor:</strong>
                                        </div>
                                        <div id="auditor-list" class="ml-4">
                                            <small class="text-muted">Pilih program studi untuk melihat tim auditor</small>
                                        </div>
                                    </div>

                                    @if ($selectedProdi)
                                        <div class="mt-3">
                                            <div class="text-center">
                                                <small class="text-muted d-block mb-2">Aksi Export & Print</small>
                                                <div class="btn-group-vertical w-100">
                                                    <!-- Button Print -->
                                                    <button onclick="window.print()" class="btn btn-info btn-sm mb-2">
                                                        <i class="fas fa-print"></i> Print
                                                    </button>

                                                    <!-- Single Export Button -->
                                                    <button type="button" onclick="openExportModal()"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-download"></i> Export Data
                                                    </button>
                                                </div>
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

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (isset($dokumenDetail) && count($dokumenDetail) > 0)
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
                                    <th class="bg-primary text-white">Bobot</th>
                                    <th class="bg-primary text-white">Tertimbang</th>
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
                                            @if (isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0)
                                                @foreach ($dokumen->nama_dokumens as $index => $nama_dokumen)
                                                    <div>{{ $nama_dokumen }}</div>
                                                    @if ($index < count($dokumen->nama_dokumens) - 1)
                                                        <hr class="my-1">
                                                    @endif
                                                @endforeach
                                            @else
                                                {{ $dokumen->nama_dokumen }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (isset($dokumen->tambahan_informasis) && count($dokumen->tambahan_informasis) > 0)
                                                @foreach ($dokumen->tambahan_informasis as $index => $tambahan_informasi)
                                                    <div>{{ $tambahan_informasi }}</div>
                                                    @if ($index < count($dokumen->tambahan_informasis) - 1)
                                                        <hr class="my-1">
                                                    @endif
                                                @endforeach
                                            @else
                                                {{ $dokumen->tambahan_informasi }}
                                            @endif
                                        </td>
                                        <td>{{ $dokumen->nilai }}</td>
                                        <td>{{ $dokumen->bobot }}</td>
                                        <td>{{ $dokumen->tertimbang }}</td>
                                        <td>
                                            @if (isset($dokumen->nama_dokumens) && count($dokumen->nama_dokumens) > 0)
                                                @foreach ($dokumen->nama_dokumens as $index => $nama_dokumen)
                                                    <div>
                                                        @if (isset($dokumen->files_dokumen) && isset($dokumen->files_dokumen[$index]))
                                                            <span class="badge badge-success">Ada</span>
                                                        @else
                                                            <span class="badge badge-danger">Tidak Ada</span>
                                                        @endif
                                                    </div>
                                                    @if ($index < count($dokumen->nama_dokumens) - 1)
                                                        <hr class="my-1">
                                                    @endif
                                                @endforeach
                                            @elseif(isset($dokumen->files_dokumen) && count($dokumen->files_dokumen) > 0)
                                                <span class="badge badge-success">Ada</span>
                                            @elseif(isset($dokumen->file_dokumen) && $dokumen->file_dokumen)
                                                <span class="badge badge-success">Ada</span>
                                            @else
                                                <span class="badge badge-danger">Tidak Ada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- Baris Total -->
                                @if (isset($totalByKriteria) && isset($totalByKriteria[$kriteria]))
                                    <tr class="bg-light font-weight-bold">
                                        <td colspan="5" class="text-right">Rata - Rata:</td>
                                        <td>{{ $totalByKriteria[$kriteria]['nilai'] }}</td>
                                        <td>{{ $totalByKriteria[$kriteria]['bobot'] }}</td>
                                        <td>{{ $totalByKriteria[$kriteria]['tertimbang'] }}</td>
                                        <td></td>
                                    </tr>
                                @endif
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

    <!-- Export Modal - SB Admin 2 Style -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="fas fa-file-export text-primary"></i> Konfigurasi Export Dokumen
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Program Studi:</strong> {{ $selectedProdi }}
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputKode"><i class="fas fa-code text-primary"></i> Kode Dokumen:</label>
                                <input type="text" id="inputKode" class="form-control"
                                    placeholder="Contoh: FM/02/LPM/03">
                                <small class="form-text text-muted">Masukkan kode dokumen sesuai standar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputTanggal"><i class="far fa-calendar-alt text-primary"></i>
                                    Tanggal:</label>
                                <input type="date" id="inputTanggal" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                                <small class="form-text text-muted">Tanggal pembuatan dokumen</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputRevisi"><i class="fas fa-edit text-primary"></i> Nomor Revisi:</label>
                        <input type="text" id="inputRevisi" class="form-control" placeholder="Contoh: Rev. 01">
                        <small class="form-text text-muted">Masukkan nomor revisi dokumen</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label><i class="fas fa-file-alt text-primary"></i> Pilih Format Export:</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <input type="radio" name="exportFormat" value="excel" id="formatExcel"
                                            checked class="sr-only">
                                        <label for="formatExcel" class="btn btn-outline-success btn-block">
                                            <i class="fas fa-file-excel fa-2x d-block mb-2"></i>
                                            <strong>Excel (.xlsx)</strong>
                                            <small class="d-block text-muted">Untuk pengolahan data</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <input type="radio" name="exportFormat" value="pdf" id="formatPdf"
                                            class="sr-only">
                                        <label for="formatPdf" class="btn btn-outline-danger btn-block">
                                            <i class="fas fa-file-pdf fa-2x d-block mb-2"></i>
                                            <strong>PDF (.pdf)</strong>
                                            <small class="d-block text-muted">Untuk cetak dan arsip</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" id="btnExport" class="btn btn-primary" onclick="processExport()">
                        <i class="fas fa-download"></i> Generate & Download
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .bg-primary {
            background-color: #4e73df !important;
        }

        .text-white {
            color: white !important;
        }

        .card-header.bg-primary {
            background-color: #4e73df !important;
        }

        .card-header.bg-primary h6 {
            color: white !important;
        }

        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        @media print {

            .navbar,
            .sidebar,
            .footer,
            .scroll-to-top,
            .btn,
            #sidebarToggleTop {
                display: none !important;
            }

            .content,
            .container,
            .container-fluid {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .bg-primary,
            .bg-dark,
            .bg-gray-200 {
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

            body {
                font-size: 10pt !important;
            }

            table td,
            table th {
                padding: 4px !important;
            }

            .card {
                margin-bottom: 10px !important;
                page-break-inside: avoid !important;
            }

            .card-header {
                padding: 6px 12px !important;
            }

            .card-body {
                padding: 10px !important;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        // =============================================
        // SB ADMIN 2 COMPATIBLE SOLUTION
        // =============================================

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

            // Update prodi name
            document.getElementById('prodi-name').textContent = selectedOption.text;

            // Update status badge
            const statusBadge = document.getElementById('prodi-status');
            statusBadge.textContent = status;

            // Set badge colors based on status
            statusBadge.className = 'badge ';
            if (status === 'Sedang Berlangsung') {
                statusBadge.className += 'badge-success';
            } else if (status === 'Selesai') {
                statusBadge.className += 'badge-info';
            } else if (status === 'Belum Dimulai') {
                statusBadge.className += 'badge-warning';
            } else {
                statusBadge.className += 'badge-secondary';
            }

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

        function openExportModal() {
            const selectedProdi = '{{ $selectedProdi }}';
            if (!selectedProdi) {
                alert('Silakan pilih program studi terlebih dahulu!');
                return;
            }

            // Reset form
            document.getElementById('inputKode').value = '';
            document.getElementById('inputTanggal').value = new Date().toISOString().split('T')[0];
            document.getElementById('inputRevisi').value = '';
            document.getElementById('formatExcel').checked = true;

            // Update button styles
            updateFormatSelection();

            // Show modal using Bootstrap 4 syntax
            $('#exportModal').modal('show');
        }

        function updateFormatSelection() {
            const excel = document.getElementById('formatExcel');
            const pdf = document.getElementById('formatPdf');
            const excelLabel = document.querySelector('label[for="formatExcel"]');
            const pdfLabel = document.querySelector('label[for="formatPdf"]');

            if (excel.checked) {
                excelLabel.className = 'btn btn-success btn-block';
                pdfLabel.className = 'btn btn-outline-danger btn-block';
            } else {
                excelLabel.className = 'btn btn-outline-success btn-block';
                pdfLabel.className = 'btn btn-danger btn-block';
            }
        }

        function processExport() {
            // Get form values
            const kode = document.getElementById('inputKode').value.trim();
            const tanggal = document.getElementById('inputTanggal').value;
            const revisi = document.getElementById('inputRevisi').value.trim();
            const format = document.querySelector('input[name="exportFormat"]:checked').value;

            // Validation
            if (!kode) {
                alert('Kode dokumen harus diisi!');
                document.getElementById('inputKode').focus();
                return;
            }
            if (!tanggal) {
                alert('Tanggal harus diisi!');
                document.getElementById('inputTanggal').focus();
                return;
            }
            if (!revisi) {
                alert('Nomor revisi harus diisi!');
                document.getElementById('inputRevisi').focus();
                return;
            }

            // Build export URL
            let exportUrl = '';
            if (format === 'excel') {
                exportUrl =
                    '{{ route('kelengkapan-dokumen.exportExcel', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}';
            } else {
                exportUrl =
                    '{{ route('kelengkapan-dokumen.exportPdf', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}';
            }

            // Build parameters
            const params = new URLSearchParams({
                prodi: '{{ $selectedProdi }}',
                kode: kode,
                tanggal: tanggal,
                revisi: revisi
            });

            // Add filter parameters
            @if (!empty($filterParams['status']))
                params.append('status', '{{ $filterParams['status'] }}');
            @endif
            @if (!empty($filterParams['year']))
                params.append('year', '{{ $filterParams['year'] }}');
            @endif
            @if (!empty($filterParams['jenjang']))
                params.append('jenjang', '{{ $filterParams['jenjang'] }}');
            @endif

            // Show loading state
            const exportBtn = document.getElementById('btnExport');
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            exportBtn.disabled = true;

            // Open export URL
            const finalUrl = `${exportUrl}?${params.toString()}`;
            window.open(finalUrl, '_blank');

            // Reset button and close modal
            setTimeout(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
                $('#exportModal').modal('hide');
            }, 2000);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize prodi info if already selected
            const prodiSelect = document.getElementById('prodi');
            if (prodiSelect && prodiSelect.value) {
                showProdiInfo(prodiSelect.value);
            }

            // Format selection handlers
            document.querySelectorAll('input[name="exportFormat"]').forEach(radio => {
                radio.addEventListener('change', updateFormatSelection);
            });

            // Session alerts
            @if (session('success'))
                // Show success message
                console.log('Success: {{ session('success') }}');
            @endif

            @if (session('error'))
                // Show error message
                console.log('Error: {{ session('error') }}');
            @endif
        });
    </script>
@endpush