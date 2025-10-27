@extends('layouts.admin')
@php
    use App\Models\PenilaianKriteria;
@endphp
@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            @if ($statusTemuan === 'TERCAPAI')
                <i class="fas fa-check-circle text-success"></i>
                Monitoring TERCAPAI
            @else
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Monitoring KTS
            @endif
            {{ $headerData->lembagaAkreditasi->nama ?? '' }}
            {{ $headerData->jenjang->nama ?? '' }}
            {{ $headerData->periode_atau_tahun ?? '' }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">{{ $selectedProdi }}</small>
            @endif
        </h1>
        <div class="btn-group">
            {{-- Button untuk Komentar Global - dynamic route --}}
            @if ($selectedProdi && isset($userPermissions) && $userPermissions['can_comment'])
                @php
                    $komentarRoute =
                        $statusTemuan === 'TERCAPAI'
                            ? 'monev-tercapai.showGlobalKomentar'
                            : 'monev-kts.showGlobalKomentar';
                @endphp
                <a href="{{ route($komentarRoute, ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId, 'prodi' => $selectedProdi]) }}"
                    class="btn btn-info btn-sm">
                    <i class="fas fa-comments"></i> Komentar Global
                </a>
            @endif

            {{-- Button kembali - dynamic route --}}
            @php
                $backRoute = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.index' : 'monev-kts.index';
            @endphp
            <a href="{{ route($backRoute) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header {{ $statusTemuan === 'TERCAPAI' ? 'bg-success' : 'bg-warning' }}">
            <h6 class="m-0 font-weight-bold {{ $statusTemuan === 'TERCAPAI' ? 'text-white' : 'text-dark' }}">
                <i class="fas fa-search"></i> Pilih Program Studi
            </h6>
        </div>
        <div class="card-body">
            <!-- Form pemilihan prodi -->
            @php
                $formRoute = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.showGroup' : 'monev-kts.showGroup';
            @endphp
            <form method="GET" action="{{ route($formRoute, ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
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
                                        data-tim-auditor="{{ isset($prodi['tim_auditor']) ? $prodi['tim_auditor'] : '' }}"
                                        data-jumlah-temuan="{{ $prodi['jumlah_temuan'] ?? 0 }}">
                                        {{ $prodi['prodi'] }}
                                        @if (isset($prodi['jumlah_temuan']) && $prodi['jumlah_temuan'] > 0)
                                            ({{ $prodi['jumlah_temuan'] }}
                                            {{ $statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS' }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Info Panel -->
                        <div id="prodi-info-panel" style="{{ $selectedProdi ? '' : 'display: none;' }}">
                            <div class="card border-left-{{ $statusTemuan === 'TERCAPAI' ? 'success' : 'warning' }} h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="font-weight-bold text-{{ $statusTemuan === 'TERCAPAI' ? 'success' : 'warning' }} mb-0"
                                            id="prodi-name">
                                            {{ $selectedProdi ?? 'Program Studi' }}
                                        </h6>
                                        {{-- Status dengan warna yang benar --}}
                                        @php
                                            $currentStatus = $currentProdiData['status'] ?? 'Status';
                                            $badgeClass = match ($currentStatus) {
                                                'Sedang Berlangsung' => 'badge-success',
                                                'Selesai' => 'badge-info',
                                                'Belum Dimulai' => 'badge-warning',
                                                default => 'badge-secondary',
                                            };
                                        @endphp
                                        <div>
                                            <span id="prodi-status" class="badge {{ $badgeClass }}">
                                                {{ $currentStatus }}
                                            </span>
                                            <span id="jumlah-temuan"
                                                class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-danger' }} ml-1">
                                                {{ $currentProdiData['jumlah_temuan'] ?? 0 }}
                                                {{ $statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i
                                                class="fas fa-users text-{{ $statusTemuan === 'TERCAPAI' ? 'success' : 'warning' }} mr-2"></i>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedProdi)
        @if ($kriteriaDokumen->isEmpty())
            <div class="alert {{ $statusTemuan === 'TERCAPAI' ? 'alert-success' : 'alert-info' }}">
                <i class="fas {{ $statusTemuan === 'TERCAPAI' ? 'fa-check-circle' : 'fa-info-circle' }}"></i>
                <strong>
                    @if ($statusTemuan === 'TERCAPAI')
                        Tidak Ada Data Tercapai!
                    @else
                        Tidak Ada Temuan KTS!
                    @endif
                </strong>
                <p class="mb-0">
                    @if ($statusTemuan === 'TERCAPAI')
                        Program studi ini tidak memiliki temuan tercapai pada periode yang dipilih.
                    @else
                        Program studi ini tidak memiliki temuan ketidaksesuaian (KTS) pada periode yang dipilih.
                    @endif
                </p>
            </div>
        @else
            @foreach ($kriteriaDokumen as $nama_kriteria => $items)
                <div class="card shadow mb-4">
                    <div class="card-header {{ $statusTemuan === 'TERCAPAI' ? 'bg-success' : 'bg-warning' }}">
                        <h6 class="m-0 font-weight-bold {{ $statusTemuan === 'TERCAPAI' ? 'text-white' : 'text-dark' }}">
                            <i
                                class="fas {{ $statusTemuan === 'TERCAPAI' ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
                            {{ $nama_kriteria }}
                            <span class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-light' : 'badge-danger' }} ml-2">
                                {{ $items->count() }} Item {{ $statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS' }}
                            </span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="{{ $statusTemuan === 'TERCAPAI' ? 'bg-success' : 'bg-danger' }} text-white">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Element</th>
                                        <th>Indikator</th>
                                        <th>Status Temuan</th>
                                        <th>Nilai</th>
                                        <th>Rekomendasi Auditor</th>
                                        <th>Komentar Follow-up</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php
                                            // Dynamic property name based on status_temuan
                                            $penilaianProperty = 'penilaian_' . strtolower($statusTemuan);
                                        @endphp

                                        @if (isset($item->$penilaianProperty))
                                            <tr
                                                class="{{ $statusTemuan === 'TERCAPAI' ? 'table-success' : 'table-warning' }}">
                                                <td>{{ $item->kode }}</td>
                                                <td>{{ $item->element }}</td>
                                                <td>{{ $item->indikator }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-danger' }}">
                                                        <i
                                                            class="fas {{ $statusTemuan === 'TERCAPAI' ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
                                                        {{ $item->$penilaianProperty->status_temuan }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-center">
                                                        <strong
                                                            class="{{ $statusTemuan === 'TERCAPAI' ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format($item->$penilaianProperty->nilai, 2) }}
                                                        </strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $item->$penilaianProperty->sebutan }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        {{ $item->$penilaianProperty->revisi ?? 'Belum ada rekomendasi' }}
                                                    </div>
                                                    @if ($item->$penilaianProperty->revisi && strlen($item->$penilaianProperty->revisi) > 50)
                                                        <button class="btn btn-sm btn-outline-info mt-1"
                                                            onclick="showFullRekomendasi('{{ addslashes($item->$penilaianProperty->revisi) }}')">
                                                            <i class="fas fa-eye"></i> Lihat Lengkap
                                                        </button>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- Kolom Komentar Follow-up --}}
                                                    <div id="komentar-display-{{ $item->id }}">
                                                        @if (isset($item->existing_comment) && $item->existing_comment)
                                                            {{-- Tampilkan komentar yang sudah ada --}}
                                                            <div class="mb-2">
                                                                <span class="text-info"
                                                                    title="{{ $item->existing_comment->komentar_element }}">
                                                                    {{ Str::limit($item->existing_comment->komentar_element, 50) }}
                                                                </span>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-user"></i>
                                                                    {{ $item->existing_comment->user->name }}
                                                                    <br>
                                                                    <i class="fas fa-clock"></i>
                                                                    {{ $item->existing_comment->updated_at->format('d/m/Y H:i') }}
                                                                </small>
                                                            </div>
                                                        @endif

                                                        {{-- Button untuk lihat/edit komentar - HANYA TAMPIL jika punya permission --}}
                                                        @if (isset($userPermissions) && $userPermissions['can_comment'])
                                                            <button class="btn btn-sm btn-warning btn-block"
                                                                onclick="editKomentarElement({{ $item->id }}, '{{ isset($item->existing_comment) ? addslashes($item->existing_comment->komentar_element ?? '') : '' }}')"
                                                                title="{{ isset($item->existing_comment) && $item->existing_comment ? 'Edit Komentar' : 'Tambah Komentar' }}">
                                                                <i class="fas fa-comment-edit"></i>
                                                                {{ isset($item->existing_comment) && $item->existing_comment ? 'Edit' : 'Tambah' }}
                                                                Komentar
                                                            </button>
                                                        @else
                                                            {{-- Jika tidak punya permission dan tidak ada komentar, tampilkan dash --}}
                                                            @if (!isset($item->existing_comment) || !$item->existing_comment)
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical">
                                                        <!-- Button untuk lihat penilaian lengkap -->
                                                        <a href="{{ route('penilaian-kriteria.index', [
                                                            'kriteriaDokumenId' => $item->id,
                                                            'informasi' => $item->informasi,
                                                            'prodi' => $selectedProdi,
                                                        ]) }}"
                                                            class="btn btn-info btn-sm mb-1" target="_blank">
                                                            <i class="fas fa-eye"></i> Lihat Penilaian
                                                        </a>

                                                        <!-- Button untuk kelola dokumen -->
                                                        <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                            'kriteriaDokumenId' => $item->id,
                                                            'prodi' => $selectedProdi,
                                                        ]) }}"
                                                            class="btn btn-secondary btn-sm" target="_blank">
                                                            <i class="fas fa-folder"></i> Kelola Dokumen
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <p class="mb-0">
                Silakan pilih Program Studi terlebih dahulu untuk melihat data
                {{ $statusTemuan === 'TERCAPAI' ? 'tercapai' : 'temuan ketidaksesuaian (KTS)' }}.
            </p>
        </div>
    @endif

    <!-- Modal untuk Rekomendasi Lengkap -->
    <div class="modal fade" id="rekomendasiModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-eye"></i> Rekomendasi Auditor Lengkap
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="fullRekomendasiContent" class="text-justify"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .modal-header {
            background-color: #f6c23e !important;
        }

        .modal-title {
            color: #000 !important;
        }

        @if ($statusTemuan === 'TERCAPAI')
            .table th {
                background-color: #28a745;
                color: white;
            }

            .card-header.bg-success h6 {
                color: white !important;
            }

            .border-left-success {
                border-left: 4px solid #28a745 !important;
            }

            .table-success {
                background-color: #d4edda !important;
            }
        @else
            .table th {
                background-color: #dc3545;
                color: white;
            }

            .card-header.bg-warning h6 {
                color: #000 !important;
            }

            .border-left-warning {
                border-left: 4px solid #ffc107 !important;
            }

            .table-warning {
                background-color: #fff3cd !important;
            }
        @endif

        .btn-group-vertical .btn {
            margin-bottom: 2px;
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
            const jumlahTemuan = selectedOption.dataset.jumlahTemuan;

            document.getElementById('prodi-name').textContent = selectedOption.text;

            const statusBadge = document.getElementById('prodi-status');
            statusBadge.textContent = status;

            // Dynamic badge for temuan count
            const statusTemuan = '{{ $statusTemuan }}';
            const temuanLabel = statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS';
            const temuanBadge = document.getElementById('jumlah-temuan');
            temuanBadge.textContent = `${jumlahTemuan} ${temuanLabel}`;
            temuanBadge.className = `badge ${statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-danger'} ml-1`;

            let badgeClass = 'badge-secondary';
            if (status === 'Sedang Berlangsung') badgeClass = 'badge-success';
            else if (status === 'Selesai') badgeClass = 'badge-info';
            else if (status === 'Belum Dimulai') badgeClass = 'badge-warning';

            statusBadge.className = `badge ${badgeClass}`;

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

        function showFullRekomendasi(rekomendasi) {
            document.getElementById('fullRekomendasiContent').innerHTML = rekomendasi.replace(/\n/g, '<br>');
            $('#rekomendasiModal').modal('show');
        }

        // Function untuk edit komentar element (mirip editKeterangan di auditor upload)
        function editKomentarElement(kriteriaId, currentKomentar) {
            Swal.fire({
                title: 'Edit Komentar Follow-up',
                input: 'textarea',
                inputLabel: 'Komentar Follow-up',
                inputValue: currentKomentar,
                inputAttributes: {
                    maxlength: 1000,
                    rows: 4,
                    placeholder: 'Tulis komentar follow-up untuk element ini...'
                },
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#ffc107',
                preConfirm: (text) => {
                    if (text && text.length > 1000) {
                        Swal.showValidationMessage('Komentar maksimal 1000 karakter');
                        return false;
                    }
                    return text;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateKomentarElement(kriteriaId, result.value || '');
                }
            });
        }

        // Function untuk update komentar element via AJAX
        function updateKomentarElement(kriteriaId, komentar) {
            Swal.fire({
                title: 'Menyimpan...',
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            @php
                $komentarRoute = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.storeKomentarElement' : 'monev-kts.storeKomentarElement';
            @endphp

            $.ajax({
                url: '{{ route($komentarRoute) }}',
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}',
                    kriteria_dokumen_id: kriteriaId,
                    prodi: '{{ $selectedProdi }}',
                    status_temuan: '{{ $statusTemuan }}',
                    komentar_element: komentar
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Komentar berhasil disimpan.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan komentar.'
                    });
                }
            });
        }

        // Initialize info panel if prodi is already selected
        document.addEventListener('DOMContentLoaded', function() {
            const prodiSelect = document.getElementById('prodi');
            if (prodiSelect.value) {
                showProdiInfo(prodiSelect.value);
            }
        });

        // SweetAlert notifications
        $(document).ready(function() {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '{{ session('success') }}',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif
        });
    </script>
@endpush
