{{-- resources/views/ptkrhr/show-group.blade.php --}}
@extends('layouts.admin')
@php
    use App\Models\PenilaianKriteria;
@endphp
@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Permintaan Tindakan Koreksi (PTK) & Rekapitulasi Hasil {{ $headerData->lembagaAkreditasi->nama ?? '' }}
            {{ $headerData->jenjang->nama ?? '' }}
            {{ $headerData->periode_atau_tahun ?? '' }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">{{ $selectedProdi }}</small>
            @endif
        </h1>
        <a href="{{ route('ptkrhr.index', [
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
        <div class="card-header bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Pilih Program Studi</h6>
        </div>
        <div class="card-body">
            @if (isset($filterParams) && count(array_filter($filterParams)) > 0)
                <div class="card filter-card border-0 mb-3">
                    <div class="card-body py-2">
                        <div class="filter-header">
                            <strong><i class="fas fa-filter"></i> Data difilter berdasarkan:</strong>
                            <a href="{{ route('ptkrhr.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                                class="btn btn-sm btn-reset-filter">
                                <i class="fas fa-times"></i> Hapus Filter
                            </a>
                        </div>

                        <div>
                            @if (!empty($filterParams['status']) && $filterParams['status'] !== 'all')
                                <span class="filter-badge">
                                    <i class="fas fa-tag"></i> Status: {{ $filterParams['status'] }}
                                </span>
                            @endif

                            @if (!empty($filterParams['year']) && $filterParams['year'] !== 'all')
                                <span class="filter-badge">
                                    <i class="far fa-calendar-alt"></i> Tahun: {{ $filterParams['year'] }}
                                </span>
                            @endif

                            @if (!empty($filterParams['jenjang']) && $filterParams['jenjang'] !== 'all')
                                <span class="filter-badge">
                                    <i class="fas fa-graduation-cap"></i> Jenjang: {{ $filterParams['jenjang'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form pemilihan prodi -->
            <form method="GET"
                action="{{ route('ptkrhr.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
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
                                        {{-- Status dengan warna yang benar seperti pemenuhan dokumen --}}
                                        <span id="prodi-status"
                                            class="badge badge-{{ isset($currentProdiData['status'])
                                                ? ($currentProdiData['status'] == 'Sedang Berlangsung'
                                                    ? 'success'
                                                    : ($currentProdiData['status'] == 'Selesai'
                                                        ? 'info'
                                                        : ($currentProdiData['status'] == 'Belum Dimulai'
                                                            ? 'warning'
                                                            : 'secondary')))
                                                : 'secondary' }}">
                                            {{ $currentProdiData['status'] ?? 'Status' }}
                                        </span>
                                    </div>

                                    <div>
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
                                                @if (!$currentProdiData['tim_auditor_detail']['ketua'] && empty($currentProdiData['tim_auditor_detail']['anggota']))
                                                    <small class="text-muted">Belum ada tim auditor</small>
                                                @endif
                                            @elseif($selectedProdi && isset($currentProdiData['tim_auditor']))
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user text-secondary mr-2"></i>
                                                    {{ $currentProdiData['tim_auditor'] }}
                                                </div>
                                            @else
                                                <small class="text-muted" id="default-auditor-text">Pilih program studi
                                                    untuk melihat tim auditor</small>
                                            @endif
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

    @if ($selectedProdi)
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
                                        <th class="bg-primary text-white">Dokumen Pendukung</th>
                                        <th class="bg-primary text-white">Dokumen TerUpload</th>
                                        <th class="bg-primary text-white">Dokumen</th>
                                        <th class="bg-primary text-white">Tanggal Pemenuhan</th>
                                        <th class="bg-primary text-white">Penanggung Jawab</th>
                                        <th class="bg-primary text-white">Status Temuan</th>
                                        <th class="bg-primary text-white">Hasil AMI</th>
                                        <th class="bg-primary text-white">Output</th>
                                        <th class="bg-primary text-white">Akar Penyebab Masalah</th>
                                        <th class="bg-primary text-white">Tinjauan Efektivitas Koreksi</th>
                                        <th class="bg-primary text-white">Kesimpulan</th>
                                        <th class="bg-primary text-white">Aksi</th>
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
                                            <!-- KOLOM BARU: Dokumen Pendukung -->
                                            <td class="text-center">{{ $dokumen->total_kebutuhan ?? 0 }}</td>
                                            <!-- KOLOM BARU: Dokumen TerUpload -->
                                            <td class="text-center">{{ $dokumen->capaian_dokumen ?? 0 }} / {{ $dokumen->total_kebutuhan ?? 0 }}</td>
                                            <!-- KOLOM BARU: Status Dokumen (Ada/Tidak Ada) -->
                                            <td class="text-center">
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
                                            <td>{{ $dokumen->tanggal_pemenuhan ?? '-' }}</td>
                                            <td>{{ $dokumen->penanggung_jawab ?? '-' }}</td>
                                            <td>{{ $dokumen->status_temuan ?? '-' }}</td>
                                            <td>{{ $dokumen->hasil_ami ?? '-' }}</td>
                                            <td>{{ $dokumen->output ?? '-' }}</td>
                                            <td>{{ $dokumen->akar_penyebab_masalah ?? '-' }}</td>
                                            <td>{{ $dokumen->tinjauan_efektivitas_koreksi ?? '-' }}</td>
                                            <td>{{ $dokumen->kesimpulan ?? '-' }}</td>
                                            <td>
                                                <div class="btn-group-vertical">
                                                    <button type="button" onclick="openExportModal({{ $dokumen->id }})"
                                                        class="btn btn-sm btn-primary mb-1">
                                                        <i class="fas fa-file-pdf"></i> PTK PDF
                                                    </button>
                                                    <button type="button"
                                                        onclick="openExportModalExcel({{ $dokumen->id }})"
                                                        class="btn btn-sm btn-success mb-1">
                                                        <i class="fas fa-file-excel"></i> PTK Excel
                                                    </button>
                                                    <button type="button"
                                                        onclick="openExportModalRekapitulasi({{ $dokumen->id }})"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="fas fa-file-pdf"></i> Rekapitulasi
                                                    </button>
                                                </div>
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
            <p class="mb-0">Silakan pilih Program Studi terlebih dahulu.</p>
        </div>
    @endif

    <!-- Export Modal PTK PDF - SB Admin 2 Style -->
    <div class="modal fade" id="exportModalPtkPdf" tabindex="-1" role="dialog"
        aria-labelledby="exportModalPtkPdfLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalPtkPdfLabel">
                        <i class="fas fa-file-pdf text-danger"></i> Konfigurasi Export PTK PDF
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
                                <label for="inputKodePtkPdf"><i class="fas fa-code text-primary"></i> Kode
                                    Dokumen:</label>
                                <input type="text" id="inputKodePtkPdf" class="form-control"
                                    placeholder="Contoh: PTK/001/2024">
                                <small class="form-text text-muted">Masukkan kode dokumen PTK</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputTanggalPtkPdf"><i class="far fa-calendar-alt text-primary"></i>
                                    Tanggal:</label>
                                <input type="date" id="inputTanggalPtkPdf" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                                <small class="form-text text-muted">Tanggal pembuatan dokumen</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputRevisiPtkPdf"><i class="fas fa-edit text-primary"></i> Nomor Revisi:</label>
                        <input type="text" id="inputRevisiPtkPdf" class="form-control" placeholder="Contoh: Rev. 01">
                        <small class="form-text text-muted">Masukkan nomor revisi dokumen</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" id="btnExportPtkPdf" class="btn btn-danger" onclick="processExportPtkPdf()">
                        <i class="fas fa-file-pdf"></i> Generate PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal PTK Excel - SB Admin 2 Style -->
    <div class="modal fade" id="exportModalPtkExcel" tabindex="-1" role="dialog"
        aria-labelledby="exportModalPtkExcelLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalPtkExcelLabel">
                        <i class="fas fa-file-excel text-success"></i> Konfigurasi Export PTK Excel
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
                                <label for="inputKodePtkExcel"><i class="fas fa-code text-primary"></i> Kode
                                    Dokumen:</label>
                                <input type="text" id="inputKodePtkExcel" class="form-control"
                                    placeholder="Contoh: PTK/001/2024">
                                <small class="form-text text-muted">Masukkan kode dokumen PTK</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputTanggalPtkExcel"><i class="far fa-calendar-alt text-primary"></i>
                                    Tanggal:</label>
                                <input type="date" id="inputTanggalPtkExcel" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                                <small class="form-text text-muted">Tanggal pembuatan dokumen</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputRevisiPtkExcel"><i class="fas fa-edit text-primary"></i> Nomor Revisi:</label>
                        <input type="text" id="inputRevisiPtkExcel" class="form-control"
                            placeholder="Contoh: Rev. 01">
                        <small class="form-text text-muted">Masukkan nomor revisi dokumen</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" id="btnExportPtkExcel" class="btn btn-success"
                        onclick="processExportPtkExcel()">
                        <i class="fas fa-file-excel"></i> Generate Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal Rekapitulasi - SB Admin 2 Style -->
    <div class="modal fade" id="exportModalRekapitulasi" tabindex="-1" role="dialog"
        aria-labelledby="exportModalRekapitulasiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalRekapitulasiLabel">
                        <i class="fas fa-file-pdf text-danger"></i> Konfigurasi Export Rekapitulasi PDF
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
                                <label for="inputKodeRekapitulasi"><i class="fas fa-code text-primary"></i> Kode
                                    Dokumen:</label>
                                <input type="text" id="inputKodeRekapitulasi" class="form-control"
                                    placeholder="Contoh: RKP/001/2024">
                                <small class="form-text text-muted">Masukkan kode dokumen Rekapitulasi</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputTanggalRekapitulasi"><i class="far fa-calendar-alt text-primary"></i>
                                    Tanggal:</label>
                                <input type="date" id="inputTanggalRekapitulasi" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                                <small class="form-text text-muted">Tanggal pembuatan dokumen</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputRevisiRekapitulasi"><i class="fas fa-edit text-primary"></i> Nomor
                            Revisi:</label>
                        <input type="text" id="inputRevisiRekapitulasi" class="form-control"
                            placeholder="Contoh: Rev. 01">
                        <small class="form-text text-muted">Masukkan nomor revisi dokumen</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" id="btnExportRekapitulasi" class="btn btn-danger"
                        onclick="processExportRekapitulasi()">
                        <i class="fas fa-file-pdf"></i> Generate PDF
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

        .card-header {
            background-color: #4e73df !important;
        }

        .card-header h6 {
            color: white !important;
        }

        /* Styling untuk filter */
        .filter-card {
            background-color: #edf7fd !important;
            border-radius: 5px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
            margin-bottom: 15px;
        }

        .filter-card .card-body {
            padding: 12px 15px;
        }

        .filter-header {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filter-header strong {
            font-size: 0.9rem;
            color: #4e73df;
        }

        .filter-badge {
            background-color: #4e73df;
            color: white;
            font-weight: normal;
            font-size: 0.85rem;
            padding: 6px 10px;
            margin-right: 8px;
            margin-bottom: 5px;
            display: inline-block;
            border-radius: 3px;
        }

        .filter-badge i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .btn-reset-filter {
            font-size: 0.8rem;
            color: #5a5c69;
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
        }

        .btn-reset-filter:hover {
            background-color: #eaecf4;
            color: #2e2f37;
        }

        @media print {

            /* Menghilangkan elemen yang tidak perlu saat print */
            .navbar,
            .sidebar,
            .footer,
            .scroll-to-top,
            .btn,
            #sidebarToggleTop {
                display: none !important;
            }

            /* Membuat layout penuh untuk konten */
            .content,
            .container,
            .container-fluid {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Memastikan semua background warna dicetak */
            .bg-primary,
            .bg-dark,
            .bg-gray-200 {
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
            table td,
            table th {
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
            .card+.card {
                page-break-before: avoid !important;
            }

            /* Atur total kumulatif agar tidak terpisah */
            .total-kumulatif {
                page-break-before: avoid !important;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        let currentDokumenId = null;

        function showProdiInfo(value) {
            const panel = document.getElementById('prodi-info-panel');

            if (!value) {
                panel.style.display = 'none';
                return;
            }

            // Get selected option data
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

            // Determine badge class based on status (sama seperti pemenuhan dokumen)
            let badgeClass = 'badge-secondary'; // Default untuk "Belum ada jadwal"
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
                // Split by | instead of comma to handle names with titles properly
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
        <div class="d-flex align-items-center">
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

        // PTK PDF Export Functions
        function openExportModal(dokumenId) {
            const selectedProdi = '{{ $selectedProdi }}';
            if (!selectedProdi) {
                alert('Silakan pilih program studi terlebih dahulu!');
                return;
            }

            currentDokumenId = dokumenId;

            // Reset form
            document.getElementById('inputKodePtkPdf').value = '';
            document.getElementById('inputTanggalPtkPdf').value = new Date().toISOString().split('T')[0];
            document.getElementById('inputRevisiPtkPdf').value = '';

            // Show modal using Bootstrap 4 syntax
            $('#exportModalPtkPdf').modal('show');
        }

        function processExportPtkPdf() {
            // Get form values
            const kode = document.getElementById('inputKodePtkPdf').value.trim();
            const tanggal = document.getElementById('inputTanggalPtkPdf').value;
            const revisi = document.getElementById('inputRevisiPtkPdf').value.trim();

            // Validation
            if (!kode) {
                alert('Kode dokumen harus diisi!');
                document.getElementById('inputKodePtkPdf').focus();
                return;
            }
            if (!tanggal) {
                alert('Tanggal harus diisi!');
                document.getElementById('inputTanggalPtkPdf').focus();
                return;
            }
            if (!revisi) {
                alert('Nomor revisi harus diisi!');
                document.getElementById('inputRevisiPtkPdf').focus();
                return;
            }

            // Build export URL manually
            const lembagaId = '{{ $lembagaId }}';
            const jenjangId = '{{ $jenjangId }}';
            const selectedProdi = encodeURIComponent('{{ $selectedProdi }}');
            const exportUrl = `/ptkrhr/${lembagaId}/${jenjangId}/pdf/${currentDokumenId}/${selectedProdi}`;

            // Build query parameters
            const params = new URLSearchParams({
                kode: kode,
                tanggal: tanggal,
                revisi: revisi
            });

            // Show loading state
            const exportBtn = document.getElementById('btnExportPtkPdf');
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
                $('#exportModalPtkPdf').modal('hide');
            }, 2000);
        }

        // PTK Excel Export Functions
        function openExportModalExcel(dokumenId) {
            const selectedProdi = '{{ $selectedProdi }}';
            if (!selectedProdi) {
                alert('Silakan pilih program studi terlebih dahulu!');
                return;
            }

            currentDokumenId = dokumenId;

            // Reset form
            document.getElementById('inputKodePtkExcel').value = '';
            document.getElementById('inputTanggalPtkExcel').value = new Date().toISOString().split('T')[0];
            document.getElementById('inputRevisiPtkExcel').value = '';

            // Show modal using Bootstrap 4 syntax
            $('#exportModalPtkExcel').modal('show');
        }

        function processExportPtkExcel() {
            // Get form values
            const kode = document.getElementById('inputKodePtkExcel').value.trim();
            const tanggal = document.getElementById('inputTanggalPtkExcel').value;
            const revisi = document.getElementById('inputRevisiPtkExcel').value.trim();

            // Validation
            if (!kode) {
                alert('Kode dokumen harus diisi!');
                document.getElementById('inputKodePtkExcel').focus();
                return;
            }
            if (!tanggal) {
                alert('Tanggal harus diisi!');
                document.getElementById('inputTanggalPtkExcel').focus();
                return;
            }
            if (!revisi) {
                alert('Nomor revisi harus diisi!');
                document.getElementById('inputRevisiPtkExcel').focus();
                return;
            }

            // Build export URL manually
            const lembagaId = '{{ $lembagaId }}';
            const jenjangId = '{{ $jenjangId }}';
            const selectedProdi = encodeURIComponent('{{ $selectedProdi }}');
            const exportUrl = `/ptkrhr/${lembagaId}/${jenjangId}/excel/${currentDokumenId}/${selectedProdi}`;

            // Build query parameters
            const params = new URLSearchParams({
                kode: kode,
                tanggal: tanggal,
                revisi: revisi
            });

            // Show loading state
            const exportBtn = document.getElementById('btnExportPtkExcel');
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
                $('#exportModalPtkExcel').modal('hide');
            }, 2000);
        }

        // Rekapitulasi Export Functions
        function openExportModalRekapitulasi(dokumenId) {
            const selectedProdi = '{{ $selectedProdi }}';
            if (!selectedProdi) {
                alert('Silakan pilih program studi terlebih dahulu!');
                return;
            }

            currentDokumenId = dokumenId;

            // Reset form
            document.getElementById('inputKodeRekapitulasi').value = '';
            document.getElementById('inputTanggalRekapitulasi').value = new Date().toISOString().split('T')[0];
            document.getElementById('inputRevisiRekapitulasi').value = '';

            // Show modal using Bootstrap 4 syntax
            $('#exportModalRekapitulasi').modal('show');
        }

        function processExportRekapitulasi() {
            // Get form values
            const kode = document.getElementById('inputKodeRekapitulasi').value.trim();
            const tanggal = document.getElementById('inputTanggalRekapitulasi').value;
            const revisi = document.getElementById('inputRevisiRekapitulasi').value.trim();

            // Validation
            if (!kode) {
                alert('Kode dokumen harus diisi!');
                document.getElementById('inputKodeRekapitulasi').focus();
                return;
            }
            if (!tanggal) {
                alert('Tanggal harus diisi!');
                document.getElementById('inputTanggalRekapitulasi').focus();
                return;
            }
            if (!revisi) {
                alert('Nomor revisi harus diisi!');
                document.getElementById('inputRevisiRekapitulasi').focus();
                return;
            }

            // Build export URL manually
            const lembagaId = '{{ $lembagaId }}';
            const jenjangId = '{{ $jenjangId }}';
            const selectedProdi = encodeURIComponent('{{ $selectedProdi }}');
            const exportUrl = `/ptkrhr/${lembagaId}/${jenjangId}/rekapitulasi-pdf/${currentDokumenId}/${selectedProdi}`;

            // Build query parameters
            const params = new URLSearchParams({
                kode: kode,
                tanggal: tanggal,
                revisi: revisi
            });

            // Show loading state
            const exportBtn = document.getElementById('btnExportRekapitulasi');
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
                $('#exportModalRekapitulasi').modal('hide');
            }, 2000);
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