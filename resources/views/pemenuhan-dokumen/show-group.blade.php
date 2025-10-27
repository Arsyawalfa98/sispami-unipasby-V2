@extends('layouts.admin')
@php
    use App\Models\PemenuhanDokumen;
    use App\Models\PenilaianKriteria;
@endphp
@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Pemenuhan Dokumen {{ $headerData->lembagaAkreditasi->nama ?? '' }}
            {{ $headerData->jenjang->nama ?? '' }}
            {{ $headerData->periode_atau_tahun ?? '' }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">{{ $selectedProdi }}</small>
            @endif
        </h1>
        <a href="{{ route('pemenuhan-dokumen.index', [
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
            <!-- Form pemilihan prodi -->
            <form method="GET"
                action="{{ route('pemenuhan-dokumen.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
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
                                        {{ $prodi['prodi'] }}
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
                        @if (isset($filterParams) && count(array_filter($filterParams)) > 0)
                            <div class="alert alert-info mb-2 py-2">
                                <small>
                                    <i class="fas fa-filter"></i> Data difilter berdasarkan kriteria:
                                    @if (!empty($filterParams['status']) && $filterParams['status'] !== 'all')
                                        <span class="badge badge-primary">Status: {{ $filterParams['status'] }}</span>
                                    @endif
                                    @if (!empty($filterParams['year']) && $filterParams['year'] !== 'all')
                                        <span class="badge badge-primary">Tahun: {{ $filterParams['year'] }}</span>
                                    @endif
                                    @if (!empty($filterParams['jenjang']) && $filterParams['jenjang'] !== 'all')
                                        <span class="badge badge-primary">Jenjang: {{ $filterParams['jenjang'] }}</span>
                                    @endif
                                </small>
                                <a href="{{ route('pemenuhan-dokumen.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                                    class="btn btn-sm btn-outline-secondary float-right">
                                    <i class="fas fa-times"></i> Hapus Filter
                                </a>
                            </div>
                        @endif

                        <!-- Info Panel -->
                        <div id="prodi-info-panel" style="{{ $selectedProdi ? '' : 'display: none;' }}">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="font-weight-bold text-primary mb-0" id="prodi-name">
                                            {{ $selectedProdi ?? 'Program Studi' }}
                                        </h6>
                                        {{-- Status dengan warna yang benar - OPTIMIZED: use pre-calculated data --}}
                                        @php
                                            $currentStatus = $currentProdiData['status'] ?? 'Status';
                                            $badgeClass = match ($currentStatus) {
                                                'Sedang Berlangsung' => 'badge-success',
                                                'Selesai' => 'badge-info',
                                                'Belum Dimulai' => 'badge-warning',
                                                default => 'badge-secondary',
                                            };
                                        @endphp
                                        <span id="prodi-status" class="badge {{ $badgeClass }}">
                                            {{ $currentStatus }}
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
                                        @php
                                            // OPTIMIZED: Use pre-calculated jadwal data from service
                                            $jadwalAmi = $jadwalData['jadwal'] ?? null;
                                            $jadwalActive = $jadwalData['active'] ?? false;
                                            $jadwalExpired = $jadwalData['expired'] ?? false;

                                            // OPTIMIZED: Use pre-calculated data from service
                                            $allCriteriaMet = $allCriteriaMet ?? true;
                                            $currentStatus = $penilaianStatus ?? PenilaianKriteria::STATUS_DRAFT;

                                            // OPTIMIZED: Use pre-calculated role data from service
                                            $isAdmin =
                                                $roleData['isAdmin'] ?? Auth::user()->hasActiveRole('Admin Prodi');
                                            $isSuperAdmin =
                                                $roleData['isSuperAdmin'] ?? Auth::user()->hasActiveRole('Super Admin');
                                            $isAdminLPM =
                                                $roleData['isAdminLPM'] ?? Auth::user()->hasActiveRole('Admin LPM');
                                            $isAuditor =
                                                $roleData['isAuditor'] ?? Auth::user()->hasActiveRole('Auditor');
                                            $adminWithFullAccess =
                                                $roleData['adminWithFullAccess'] ?? $isSuperAdmin || $isAdminLPM;
                                            $hasPenilaian = !is_null($penilaianStatus);

                                            $hasValidDataForButton = $kriteriaDokumen
                                                ->flatten()
                                                ->filter(function ($item) {
                                                    return !is_null($item->kode) &&
                                                        !is_null($item->element) &&
                                                        !is_null($item->indikator) &&
                                                        !is_null($item->kebutuhan_dokumen);
                                                })
                                                ->isNotEmpty();
                                        @endphp

                                        <!-- Tampilkan notifikasi jadwal jika sudah lewat -->
                                        @if ($jadwalExpired)
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-clock"></i> Jadwal AMI untuk periode ini telah berakhir
                                                pada
                                                {{ $jadwalAmi ? \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai)->format('d M Y H:i') : 'tanggal tidak diketahui' }}.
                                                Anda masih dapat melihat data, tetapi tidak dapat melakukan perubahan
                                                status.
                                            </div>
                                        @endif

                                        <!-- Status dan Control Panel -->
                                        <div class="mt-3">
                                            <div class="text-center">
                                                <!-- Status Display -->
                                                <div class="mb-2">
                                                    <strong class="small text-gray-700">Status Penilaian:</strong><br>
                                                    <span
                                                        class="badge badge-{{ $currentStatus != PenilaianKriteria::STATUS_DRAFT ? 'info' : ($allCriteriaMet ? 'success' : 'warning') }}">
                                                        {{ strtoupper($currentStatus) }}
                                                    </span>
                                                </div>

                                                <!-- Button Lihat Detail Lengkap -->
                                                @if ($hasPenilaian)
                                                    <a href="{{ route('pemenuhan-dokumen.detail', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId, 'prodi' => $selectedProdi]) }}"
                                                        class="btn btn-success btn-sm btn-block mb-2">
                                                        <i class="fas fa-eye"></i> Lihat Detail Lengkap
                                                    </a>
                                                @endif

                                                <!-- Action Buttons for different roles -->
                                                @if (!$jadwalExpired)
                                                    @if ($isAdmin)
                                                        <!-- Tombol untuk Admin Prodi -->
                                                        @if ($currentStatus == PenilaianKriteria::STATUS_DRAFT)
                                                            @if ($allCriteriaMet && $hasValidDataForButton && $statusDokumen == true)
                                                                <button type="button"
                                                                    class="btn btn-primary btn-sm btn-block mb-2"
                                                                    onclick="handleAjukan()">
                                                                    <i class="fas fa-paper-plane"></i> Ajukan
                                                                </button>
                                                            @else
                                                                <small class="text-info d-block mb-2">Silahkan memenuhi
                                                                    dokumen terlebih dahulu</small>
                                                            @endif
                                                        @elseif($currentStatus == PenilaianKriteria::STATUS_DIAJUKAN)
                                                            <small class="text-primary d-block mb-2">Silahkan menunggu hasil
                                                                pemeriksaan</small>
                                                        @endif
                                                    @elseif($isAuditor || $adminWithFullAccess)
                                                        <!-- Tombol untuk Auditor dan Admin dengan Full Access -->
                                                        @if ($currentStatus == PenilaianKriteria::STATUS_DIAJUKAN)
                                                            <div class="mb-2">
                                                                <!-- Status Info Button (Button Informasi Penilaian) -->
                                                                <button type="button"
                                                                    class="btn btn-info btn-sm btn-block mb-2"
                                                                    data-info="1. DISETUJUI = Dokumen dan penilaian telah memenuhi standar kualitas yang ditetapkan. Semua kriteria telah terpenuhi dengan baik dan tidak ada perbaikan yang diperlukan. Status ini menandakan bahwa proses akreditasi untuk kriteria ini telah selesai dan berhasil."
                                                                    onclick="showStatusModal(this)">
                                                                    <i class="fas fa-info-circle"></i> Informasi Penilaian
                                                                </button>

                                                                <!-- Dropdown Status -->
                                                                <select name="status"
                                                                    class="form-control form-control-sm mb-2"
                                                                    id="statusSelect">
                                                                    <option value="">Pilih Status</option>
                                                                    <option
                                                                        value="{{ PenilaianKriteria::STATUS_DISETUJUI }}">
                                                                        Disetujui</option>
                                                                </select>

                                                                <button type="button"
                                                                    class="btn btn-primary btn-sm btn-block"
                                                                    onclick="handleStatusChange()">
                                                                    <i class="fas fa-paper-plane"></i> Kirim Status
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @else
                                                    <!-- Tampilkan pesan jika jadwal sudah berakhir -->
                                                    <div class="alert alert-secondary">
                                                        <i class="fas fa-lock"></i> Perubahan status tidak diizinkan karena
                                                        jadwal AMI telah berakhir.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Form untuk update status (terpisah) -->
            <form id="statusForm"
                action="{{ route('pemenuhan-dokumen.updateStatus', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                method="POST" style="display: none;">
                @csrf
                <input type="hidden" name="prodi" value="{{ $selectedProdi }}">
                <input type="hidden" name="status" id="hiddenStatus">
            </form>

            <!-- Form untuk submit pengajuan -->
            <form id="ajukanForm"
                action="{{ route('pemenuhan-dokumen.ajukan', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}"
                method="POST" style="display: none;">
                @csrf
                <input type="hidden" name="prodi" value="{{ $selectedProdi }}">
            </form>
        </div>
    </div>

    @if ($selectedProdi)
        @php
            $hasValidData = $kriteriaDokumen
                ->flatten()
                ->filter(function ($item) {
                    return !is_null($item->kode) &&
                        !is_null($item->element) &&
                        !is_null($item->indikator) &&
                        !is_null($item->kebutuhan_dokumen);
                })
                ->isNotEmpty();
        @endphp

        @if (!$hasValidData)
            <div class="alert alert-info">
                <p class="mb-0">Belum ada data kriteria yang lengkap untuk prodi ini.</p>
            </div>
        @else
            @foreach ($kriteriaDokumen as $nama_kriteria => $items)
                @php
                    // Filter items yang memiliki data lengkap
                    $filledItems = $items->filter(function ($item) {
                        return $item->kode &&
                            $item->element &&
                            $item->indikator &&
                            $item->informasi &&
                            $item->kebutuhan_dokumen;
                    });
                @endphp
                @if ($filledItems->isNotEmpty())
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary">
                            <h6 class="m-0 font-weight-bold text-white">{{ $nama_kriteria }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Element</th>
                                            <th>Indikator</th>
                                            <th>Capaian</th>
                                            <th>Dokumen Pendukung</th>
                                            <th>Dokumen TerUpload</th>
                                            @if (
                                                !(
                                                    $isAdmin &&
                                                    ($currentStatus == PenilaianKriteria::STATUS_DIAJUKAN || $currentStatus == PenilaianKriteria::STATUS_DRAFT)
                                                ))
                                                <th>Nilai(Capaian)</th>
                                                <th>Sebutan</th>
                                            @endif
                                            <th>Bobot</th>
                                            <th>Tertimbang</th>
                                            <th hidden>Nilai Auditor</th>
                                            <th>Status</th>
                                            @if (!($isAdmin && $currentStatus == PenilaianKriteria::STATUS_DIAJUKAN))
                                                <th>Dokumen Aksi</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>{{ $item->kode }}</td>
                                                <td>{{ $item->element }}</td>
                                                <td>{{ $item->indikator }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-info btn-sm info-btn"
                                                        data-info="{{ $item->informasi }}"
                                                        data-indikator="{{ $item->informasi }}">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </td>
                                                <td>{{ $item->total_kebutuhan }}</td>
                                                <td>{{ $item->capaian_dokumen }} / {{ $item->total_kebutuhan }}
                                                </td>

                                                {{-- OPTIMIZED: Use pre-loaded penilaian data instead of query --}}
                                                @php
                                                    $penilaian = $penilaianData->get($item->id);
                                                @endphp

                                                @if (
                                                    !(
                                                        $isAdmin &&
                                                        ($currentStatus == PenilaianKriteria::STATUS_DIAJUKAN || $currentStatus == PenilaianKriteria::STATUS_DRAFT)
                                                    ))
                                                    <td>{{ $penilaian && !is_null($penilaian->nilai) ? number_format($penilaian->nilai, 2) : '-' }}
                                                    </td>
                                                    <td>
                                                        @if ($penilaian && $penilaian->sebutan)
                                                            @php
                                                                $badgeClass = match ($penilaian->sebutan) {
                                                                    'Sangat Baik' => 'badge-success',
                                                                    'Baik' => 'badge-info',
                                                                    'Cukup' => 'badge-primary',
                                                                    'Kurang' => 'badge-warning',
                                                                    'Sangat Kurang' => 'badge-danger',
                                                                    default => 'badge-secondary',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $badgeClass }}">
                                                                {{ $penilaian->sebutan }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">-</span>
                                                        @endif
                                                    </td>
                                                @endif

                                                <td>{{ $item->bobot ?? 'Bobot Belum Di Isi' }}</td>
                                                <td>{{ $penilaian && !is_null($penilaian->tertimbang) ? number_format($penilaian->tertimbang, 2) : '-' }}
                                                </td>
                                                <td hidden>
                                                    {{ $penilaian && !is_null($penilaian->nilai_auditor) ? number_format($penilaian->nilai_auditor, 2) : '-' }}
                                                </td>

                                                {{-- Status Column --}}
                                                <td>
                                                    @if ($currentStatus == PenilaianKriteria::STATUS_DIAJUKAN)
                                                        <span class="badge badge-primary">DIAJUKAN</span>
                                                    @elseif($currentStatus == PenilaianKriteria::STATUS_DISETUJUI)
                                                        <span class="badge badge-success">DISETUJUI</span>
                                                    @elseif($currentStatus == PenilaianKriteria::STATUS_DRAFT)
                                                        <span class="badge badge-warning">DRAFT</span>
                                                    @endif
                                                </td>

                                                {{-- Dokumen Aksi Column --}}
                                                <td>
                                                    @if ($adminWithFullAccess)
                                                        <!-- Super Admin dan Admin LPM selalu memiliki akses penuh (pemantauan) -->
                                                        <a href="{{ route('penilaian-kriteria.index', [
                                                            'kriteriaDokumenId' => $item->id,
                                                            'informasi' => $item->informasi,
                                                            'prodi' => $selectedProdi,
                                                        ]) }}"
                                                            class="btn btn-warning btn-sm mb-1">
                                                            <i class="fas fa-star"></i> Isi Nilai
                                                        </a>
                                                        <br>
                                                        <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                            'kriteriaDokumenId' => $item->id,
                                                            'prodi' => $selectedProdi,
                                                        ]) }}"
                                                            class="btn btn-info btn-sm">
                                                            <i class="fas fa-cogs"></i> Kelola Dokumen
                                                        </a>
                                                    @elseif($isAdmin)
                                                        <!-- Admin Prodi - aktif hanya di DRAFT dan DISETUJUI -->
                                                        @if ($currentStatus == PenilaianKriteria::STATUS_DRAFT)
                                                            <!-- Status DRAFT - hanya bisa kelola dokumen -->
                                                            <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fas fa-cogs"></i> Kelola Dokumen
                                                            </a>
                                                        @elseif($currentStatus == PenilaianKriteria::STATUS_DISETUJUI)
                                                            <!-- Status DISETUJUI - bisa nilai dan kelola dokumen -->
                                                            <a href="{{ route('penilaian-kriteria.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'informasi' => $item->informasi,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-warning btn-sm mb-1">
                                                                <i class="fas fa-star"></i> Nilai
                                                            </a>
                                                            <br>
                                                            <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fas fa-cogs"></i> Kelola Dokumen
                                                            </a>
                                                        @endif
                                                    @elseif($isAuditor)
                                                        <!-- Auditor - aktif di DIAJUKAN dan DISETUJUI -->
                                                        @if ($currentStatus == PenilaianKriteria::STATUS_DIAJUKAN || $currentStatus == PenilaianKriteria::STATUS_DISETUJUI)
                                                            <!-- Auditor bisa nilai di kedua status ini -->
                                                            <a href="{{ route('penilaian-kriteria.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'informasi' => $item->informasi,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-warning btn-sm mb-1">
                                                                <i class="fas fa-star"></i> Isi Nilai
                                                            </a>
                                                            <br>
                                                            <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fas fa-cogs"></i> Kelola Dokumen
                                                            </a>
                                                        @endif
                                                    @else
                                                        <!-- Role lainnya (termasuk Fakultas) - sama seperti Admin Prodi -->
                                                        @if ($currentStatus == PenilaianKriteria::STATUS_DRAFT)
                                                            <!-- Status DRAFT - hanya bisa kelola dokumen -->
                                                            <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fas fa-cogs"></i> Kelola Dokumen
                                                            </a>
                                                        @elseif($currentStatus == PenilaianKriteria::STATUS_DISETUJUI)
                                                            <!-- Status DISETUJUI - bisa nilai dan kelola dokumen -->
                                                            <a href="{{ route('penilaian-kriteria.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'informasi' => $item->informasi,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-warning btn-sm mb-1">
                                                                <i class="fas fa-star"></i> Nilai
                                                            </a>
                                                            <br>
                                                            <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
                                                                'kriteriaDokumenId' => $item->id,
                                                                'prodi' => $selectedProdi,
                                                            ]) }}"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fas fa-cogs"></i> Kelola Dokumen
                                                            </a>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    @else
        <div class="alert alert-info">
            <p class="mb-0">Silakan pilih Program Studi terlebih dahulu.</p>
        </div>
    @endif

    <!-- Modal Informasi -->
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="infoModalLabel">Informasi Penilaian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="font-weight-bold mb-3">ISI INFORMASI:</h6>
                    <p id="modalIndikator" style="white-space: pre-wrap;"></p>
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

        .table th {
            background-color: #4e73df;
            color: white;
        }

        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
            color: white;
        }

        .btn-info:hover {
            background-color: #2fa4b5;
            border-color: #2fa4b5;
            color: white;
        }

        .card-header {
            background-color: #4e73df !important;
        }

        .card-header h6 {
            color: white !important;
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

            // Get selected option data
            const selectedOption = document.querySelector(`option[value="${value}"]`);
            if (!selectedOption) return;

            const status = selectedOption.dataset.status;
            const ketua = selectedOption.dataset.ketua;
            const anggota = selectedOption.dataset.anggota;
            const timAuditor = selectedOption.dataset.timAuditor;

            // Update panel content
            document.getElementById('prodi-name').textContent = selectedOption.text;

            // Update status badge dengan warna yang benar
            const statusBadge = document.getElementById('prodi-status');
            statusBadge.textContent = status;

            // Determine badge class based on status
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
                // Split by | to handle names with titles properly
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

        // Function untuk menampilkan modal informasi penilaian
        function showStatusModal(button) {
            const statusInfo = button.getAttribute('data-info');
            $('#modalIndikator').css('white-space', 'pre-wrap').css('text-align', 'left').html(statusInfo);
            $('#infoModal').modal('show');
        }

        // Function untuk handle pengajuan
        function handleAjukan() {
            Swal.fire({
                title: 'Konfirmasi Pengajuan',
                text: "Apakah Anda yakin ingin mengajukan dokumen ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ajukan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('ajukanForm').submit();
                }
            });
        }

        // Function untuk handle perubahan status
        function handleStatusChange() {
            const statusSelect = document.getElementById('statusSelect');
            const hiddenStatus = document.getElementById('hiddenStatus');

            if (!statusSelect.value) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Silakan pilih status terlebih dahulu!'
                });
                return;
            }

            // Set nilai status ke hidden input
            hiddenStatus.value = statusSelect.value;

            Swal.fire({
                title: 'Konfirmasi Perubahan Status',
                text: "Apakah Anda yakin ingin mengubah status?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('statusForm').submit();
                }
            });
        }

        // Document ready functions
        $(document).ready(function() {
            // Modal handling for info buttons in table
            $(document).on('click', '.info-btn', function() {
                const indikator = $(this).data('indikator');
                $('#modalIndikator').text(indikator);
                $('#infoModal').modal('show');
            });

            // Handler untuk menutup modal
            $('#infoModal').on('hidden.bs.modal', function() {
                $('#modalIndikator').text('');
            });

            // SweetAlert notifications
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

        // Initialize info panel if prodi is already selected
        document.addEventListener('DOMContentLoaded', function() {
            const prodiSelect = document.getElementById('prodi');
            if (prodiSelect.value) {
                showProdiInfo(prodiSelect.value);
            }

            // Simpan nilai awal status
            const statusSelect = document.querySelector('select[name="status"]');
            if (statusSelect) {
                statusSelect.setAttribute('data-original', statusSelect.value);
            }
        });
    </script>
@endpush
