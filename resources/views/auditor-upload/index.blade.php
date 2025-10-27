{{-- resources/views/auditor-upload/index.blade.php --}}
@extends('layouts.admin')

@php
    use Carbon\Carbon;
@endphp

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Upload File Auditor</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('auditor-upload.index') }}" class="mb-0">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari berdasarkan nama lembaga, tahun..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="year" class="form-control" onchange="this.form.submit()">
                            <option value="all" {{ request('year') == 'all' || !request('year') ? 'selected' : '' }}>
                                Semua Tahun</option>
                            @foreach ($yearOptions as $yearOption)
                                <option value="{{ $yearOption }}" {{ request('year') == $yearOption ? 'selected' : '' }}>
                                    {{ $yearOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="jenjang" class="form-control" onchange="this.form.submit()">
                            <option value="all"
                                {{ request('jenjang') == 'all' || !request('jenjang') ? 'selected' : '' }}>Semua Jenjang
                            </option>
                            @foreach ($jenjangOptions as $jenjangId => $jenjangNama)
                                <option value="{{ $jenjangNama }}"
                                    {{ request('jenjang') == $jenjangNama ? 'selected' : '' }}>
                                    {{ $jenjangNama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 text-right">
                        <a href="{{ route('auditor-upload.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all"
                                {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>Semua Status
                                Upload
                            </option>
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}"
                                    {{ request('status') == $statusOption ? 'selected' : '' }}>
                                    {{ $statusOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">* Gunakan filter di atas untuk mempermudah pencarian data</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            @if ($kriteriaDokumen->isEmpty())
                <div class="alert alert-info">
                    Tidak ada data upload auditor yang tersedia.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th width="5%">No</th>
                                <th>Lembaga Akreditasi</th>
                                <th>Periode/Tahun</th>
                                <th>Jenjang</th>
                                <th>Jadwal Upload & Status</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kriteriaDokumen as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->lembagaAkreditasi->nama }}</td>
                                    <td>{{ $item->periode_atau_tahun }}</td>
                                    <td>{{ $item->jenjang->nama }}</td>
                                    <td>
                                        @if ($item->filtered_details->isNotEmpty())
                                            @foreach ($item->filtered_details as $detail)
                                                @if (Auth::user()->hasActiveRole('Admin Prodi'))
                                                    @php
                                                        $userProdi = getActiveProdi();
                                                        $kodeProdi = trim(explode('-', $userProdi)[0]);
                                                    @endphp
                                                    @if (str_starts_with($detail['prodi'], $kodeProdi))
                                                        <div class="mb-2">
                                                            <strong>{{ $detail['prodi'] }}:</strong>
                                                            <div>
                                                                <span class="text-muted">Status Upload:</span>
                                                                <span class="badge {{ $detail['upload_status_badge'] }}">
                                                                    {{ $detail['upload_status'] }}
                                                                </span>
                                                            </div>

                                                            @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                                <div>
                                                                    <span class="text-muted">Jadwal AMI:</span>
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('d/m/Y H:i') }}
                                                                    s/d
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('d/m/Y H:i') }}
                                                                </div>

                                                                @if ($detail['can_access_upload'])
                                                                    <div>
                                                                        <span class="text-muted">Jadwal Upload:</span>
                                                                        {{ Carbon::parse($detail['upload_mulai'])->format('d/m/Y H:i') }}
                                                                        s/d
                                                                        {{ Carbon::parse($detail['upload_selesai'])->format('d/m/Y H:i') }}
                                                                    </div>
                                                                @endif

                                                                <div>
                                                                    <span class="text-muted">Tim Auditor:</span>
                                                                    @include('shared.tim-auditor-display', [
                                                                        'timAuditorString' =>
                                                                            $detail['tim_auditor'],
                                                                        'timAuditorDetail' =>
                                                                            $detail['tim_auditor_detail'] ?? null,
                                                                    ])
                                                                </div>

                                                                @if ($detail['upload_stats']['total_files'] > 0)
                                                                    <div>
                                                                        <span class="text-muted">File Upload:</span>
                                                                        <span
                                                                            class="badge badge-info">{{ $detail['upload_stats']['total_files'] }}
                                                                            file(s)</span>
                                                                    </div>
                                                                @endif

                                                                @if ($detail['upload_stats']['total_auditors'] > 0)
                                                                    <div>
                                                                        <span class="text-muted">Progress:</span>
                                                                        {{ $detail['upload_stats']['auditors_uploaded'] }}/{{ $detail['upload_stats']['total_auditors'] }}
                                                                        auditor
                                                                    </div>
                                                                @endif
                                                            @else
                                                                <div>
                                                                    <span class="text-muted">Status:</span>
                                                                    <span
                                                                        class="badge badge-warning">{{ $detail['status'] }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @elseif(Auth::user()->hasActiveRole('Fakultas'))
                                                    @php
                                                        $userFakultas = getActiveFakultas();
                                                        $detailFakultas = isset($detail['fakultas'])
                                                            ? $detail['fakultas']
                                                            : '';
                                                    @endphp
                                                    @if ($detailFakultas == $userFakultas)
                                                        <div class="mb-2">
                                                            <strong>{{ $detail['prodi'] }}:</strong>
                                                            <div>
                                                                <span class="text-muted">Status Upload:</span>
                                                                <span class="badge {{ $detail['upload_status_badge'] }}">
                                                                    {{ $detail['upload_status'] }}
                                                                </span>
                                                            </div>

                                                            @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                                <div>
                                                                    <span class="text-muted">Jadwal AMI:</span>
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('d/m/Y H:i') }}
                                                                    s/d
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('d/m/Y H:i') }}
                                                                </div>

                                                                @if ($detail['can_access_upload'])
                                                                    <div>
                                                                        <span class="text-muted">Jadwal Upload:</span>
                                                                        {{ Carbon::parse($detail['upload_mulai'])->format('d/m/Y H:i') }}
                                                                        s/d
                                                                        {{ Carbon::parse($detail['upload_selesai'])->format('d/m/Y H:i') }}
                                                                    </div>
                                                                @endif

                                                                <div>
                                                                    <span class="text-muted">Tim Auditor:</span>
                                                                    @include('shared.tim-auditor-display', [
                                                                        'timAuditorString' =>
                                                                            $detail['tim_auditor'],
                                                                        'timAuditorDetail' =>
                                                                            $detail['tim_auditor_detail'] ?? null,
                                                                    ])
                                                                </div>

                                                                @if ($detail['upload_stats']['total_files'] > 0)
                                                                    <div>
                                                                        <span class="text-muted">File Upload:</span>
                                                                        <span
                                                                            class="badge badge-info">{{ $detail['upload_stats']['total_files'] }}
                                                                            file(s)</span>
                                                                    </div>
                                                                @endif
                                                            @else
                                                                <div>
                                                                    <span class="text-muted">Status:</span>
                                                                    <span
                                                                        class="badge badge-warning">{{ $detail['status'] }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @elseif(Auth::user()->hasActiveRole('Auditor'))
                                                    {{-- Auditor hanya lihat yang ditugaskan --}}
                                                    @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                        @php
                                                            $isAssigned = false;
                                                            if (isset($detail['tim_auditor_detail'])) {
                                                                $isAssigned = in_array(
                                                                    Auth::user()->name,
                                                                    array_merge(
                                                                        [$detail['tim_auditor_detail']['ketua'] ?? ''],
                                                                        $detail['tim_auditor_detail']['anggota'] ?? [],
                                                                    ),
                                                                );
                                                            } elseif (isset($detail['tim_auditor'])) {
                                                                $isAssigned = str_contains(
                                                                    $detail['tim_auditor'],
                                                                    Auth::user()->name,
                                                                );
                                                            }
                                                        @endphp
                                                        @if ($isAssigned)
                                                            <div class="mb-2">
                                                                <strong>{{ $detail['prodi'] }}:</strong>
                                                                <div>
                                                                    <span class="text-muted">Status Upload:</span>
                                                                    <span
                                                                        class="badge {{ $detail['upload_status_badge'] }}">
                                                                        {{ $detail['upload_status'] }}
                                                                    </span>
                                                                </div>

                                                                @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                                    <div>
                                                                        <span class="text-muted">Jadwal AMI:</span>
                                                                        {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('d/m/Y H:i') }}
                                                                        s/d
                                                                        {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('d/m/Y H:i') }}
                                                                    </div>

                                                                    @if ($detail['can_access_upload'])
                                                                        <div>
                                                                            <span class="text-muted">Jadwal Upload:</span>
                                                                            {{ Carbon::parse($detail['upload_mulai'])->format('d/m/Y H:i') }}
                                                                            s/d
                                                                            {{ Carbon::parse($detail['upload_selesai'])->format('d/m/Y H:i') }}
                                                                        </div>
                                                                    @endif

                                                                    <div>
                                                                        <span class="text-muted">Tim Auditor:</span>
                                                                        @include(
                                                                            'shared.tim-auditor-display',
                                                                            [
                                                                                'timAuditorString' =>
                                                                                    $detail['tim_auditor'],
                                                                                'timAuditorDetail' =>
                                                                                    $detail[
                                                                                        'tim_auditor_detail'
                                                                                    ] ?? null,
                                                                            ]
                                                                        )
                                                                    </div>

                                                                    @if ($detail['upload_stats']['total_files'] > 0)
                                                                        <div>
                                                                            <span class="text-muted">File Upload:</span>
                                                                            <span
                                                                                class="badge badge-info">{{ $detail['upload_stats']['total_files'] }}
                                                                                file(s)</span>
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                    <div>
                                                                        <span class="text-muted">Status:</span>
                                                                        <span
                                                                            class="badge badge-warning">{{ $detail['status'] }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                @else
                                                    {{-- Super Admin/Admin LPM - tampilkan semua --}}
                                                    <div class="mb-2">
                                                        <strong>{{ $detail['prodi'] }}:</strong>
                                                        <div>
                                                            <span class="text-muted">Status Upload:</span>
                                                            <span class="badge {{ $detail['upload_status_badge'] }}">
                                                                {{ $detail['upload_status'] }}
                                                            </span>
                                                        </div>

                                                        @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                            <div>
                                                                <span class="text-muted">Jadwal AMI:</span>
                                                                {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('d/m/Y H:i') }}
                                                                s/d
                                                                {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('d/m/Y H:i') }}
                                                            </div>

                                                            @if ($detail['can_access_upload'])
                                                                <div>
                                                                    <span class="text-muted">Jadwal Upload:</span>
                                                                    {{ Carbon::parse($detail['upload_mulai'])->format('d/m/Y H:i') }}
                                                                    s/d
                                                                    {{ Carbon::parse($detail['upload_selesai'])->format('d/m/Y H:i') }}
                                                                </div>
                                                            @endif

                                                            <div>
                                                                <span class="text-muted">Tim Auditor:</span>
                                                                @include('shared.tim-auditor-display', [
                                                                    'timAuditorString' => $detail['tim_auditor'],
                                                                    'timAuditorDetail' =>
                                                                        $detail['tim_auditor_detail'] ?? null,
                                                                ])
                                                            </div>

                                                            @if ($detail['upload_stats']['total_files'] > 0)
                                                                <div>
                                                                    <span class="text-muted">File Upload:</span>
                                                                    <span
                                                                        class="badge badge-info">{{ $detail['upload_stats']['total_files'] }}
                                                                        file(s)</span>
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div>
                                                                <span class="text-muted">Status:</span>
                                                                <span
                                                                    class="badge badge-warning">{{ $detail['status'] }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::user()->hasActiveRole('Auditor'))
                                            @php
                                                $hasAssignedUpload = false;
                                                $matchedJadwalId = null;

                                                foreach ($item->filtered_details as $detail) {
                                                    if (isset($detail['jadwal']) && $detail['can_access_upload']) {
                                                        $isAssigned = false;
                                                        if (isset($detail['tim_auditor_detail'])) {
                                                            $isAssigned = in_array(
                                                                Auth::user()->name,
                                                                array_merge(
                                                                    [$detail['tim_auditor_detail']['ketua'] ?? ''],
                                                                    $detail['tim_auditor_detail']['anggota'] ?? [],
                                                                ),
                                                            );
                                                        } elseif (isset($detail['tim_auditor'])) {
                                                            $isAssigned = str_contains(
                                                                $detail['tim_auditor'],
                                                                Auth::user()->name,
                                                            );
                                                        }

                                                        if ($isAssigned) {
                                                            $hasAssignedUpload = true;
                                                            $matchedJadwalId = $detail['jadwal_ami_id'];
                                                            break;
                                                        }
                                                    }
                                                }
                                            @endphp

                                            @if ($hasAssignedUpload && $matchedJadwalId)
                                                <a href="{{ route('auditor-upload.showGroupByLembaga', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'periode' => $item->periode_atau_tahun,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-upload"></i> UPLOAD
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Upload tidak tersedia</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Admin Prodi'))
                                            @php
                                                $userProdi = getActiveProdi();
                                                $kodeProdi = trim(explode('-', $userProdi)[0]);
                                                $hasMatchingUpload = false;
                                                $matchedJadwalId = null;

                                                foreach ($item->filtered_details as $detail) {
                                                    if (
                                                        str_starts_with($detail['prodi'], $kodeProdi) &&
                                                        $detail['can_access_upload']
                                                    ) {
                                                        $hasMatchingUpload = true;
                                                        $matchedJadwalId = $detail['jadwal_ami_id'];
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            @if ($hasMatchingUpload && $matchedJadwalId)
                                                <a href="{{ route('auditor-upload.showGroupByLembaga', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'periode' => $item->periode_atau_tahun,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> KELOLA
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Upload tidak tersedia</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Fakultas'))
                                            @php
                                                $userFakultas = getActiveFakultas();
                                                $hasMatchingUpload = false;
                                                $firstJadwalId = null;

                                                foreach ($item->filtered_details as $detail) {
                                                    $detailFakultas = isset($detail['fakultas'])
                                                        ? $detail['fakultas']
                                                        : '';
                                                    if (
                                                        $detailFakultas == $userFakultas &&
                                                        $detail['can_access_upload']
                                                    ) {
                                                        $hasMatchingUpload = true;
                                                        $firstJadwalId = $detail['jadwal_ami_id'];
                                                        break; // Ambil yang pertama dan keluar
                                                    }
                                                }
                                            @endphp

                                            @if ($hasMatchingUpload && $firstJadwalId)
                                                <a href="{{ route('auditor-upload.showGroupByLembaga', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'periode' => $item->periode_atau_tahun,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> KELOLA
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Upload tidak tersedia</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Admin PPG'))
                                            @php
                                                $hasMatchingUpload = false;
                                                $matchedJadwalId = null;

                                                foreach ($item->filtered_details as $detail) {
                                                    $isPrograPromoPPG =
                                                        str_contains(strtolower($detail['prodi']), 'profesi') ||
                                                        str_contains(strtolower($detail['prodi']), 'ppg') ||
                                                        str_contains(strtolower($detail['prodi']), 'program profesi');

                                                    if ($isPrograPromoPPG && $detail['can_access_upload']) {
                                                        $hasMatchingUpload = true;
                                                        $matchedJadwalId = $detail['jadwal_ami_id'];
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            @if ($hasMatchingUpload && $matchedJadwalId)
                                                <a href="{{ route('auditor-upload.showGroupByLembaga', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'periode' => $item->periode_atau_tahun,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> KELOLA
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Upload tidak tersedia</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM'))
                                            @php
                                                $hasAnyUpload = false;
                                                $firstJadwalId = null;
                                                foreach ($item->filtered_details as $detail) {
                                                    if ($detail['can_access_upload']) {
                                                        $hasAnyUpload = true;
                                                        $firstJadwalId = $detail['jadwal_ami_id'];
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            @if ($hasAnyUpload && $firstJadwalId)
                                                <a href="{{ route('auditor-upload.showGroupByLembaga', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'periode' => $item->periode_atau_tahun,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-cogs"></i> KELOLA
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Upload tidak tersedia</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $kriteriaDokumen->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('css')
    <style>
        /* Tebalkan border tabel */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
    </style>
@endpush

@push('js')
    <script>
        $(document).ready(function() {
            // Success/Error notifications
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
                    timer: 5000,
                    showConfirmButton: true
                });
            @endif
        });
    </script>
@endpush
