{{-- resources/views/pemenuhan-dokumen/index.blade.php --}}
@extends('layouts.admin')

@php
    use Carbon\Carbon;
@endphp

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Pemenuhan Dokumen</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('pemenuhan-dokumen.index') }}" class="mb-0">
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
                        <a href="{{ route('pemenuhan-dokumen.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all"
                                {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>Semua Status
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
                    Tidak ada data pemenuhan dokumen yang tersedia.
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
                                <th>Jadwal AMI</th>
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
                                                            @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                                <div>
                                                                    <span class="text-muted">Jadwal:</span>
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('Y-m-d H:i:s') }}
                                                                    s/d
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('Y-m-d H:i:s') }}
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted">Status:</span>
                                                                    <span
                                                                        class="badge {{ $detail['status'] == 'Sedang Berlangsung' ? 'badge-success' : 'badge-secondary' }}">
                                                                        {{ $detail['status'] }}
                                                                    </span>
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted">Tim Auditor:</span>
                                                                    @include('shared.tim-auditor-display', [
                                                                        'timAuditorString' =>
                                                                            $detail['tim_auditor'],
                                                                        'timAuditorDetail' =>
                                                                            $detail['tim_auditor_detail'] ?? null,
                                                                    ])
                                                                </div>
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
                                                            @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                                <div>
                                                                    <span class="text-muted">Jadwal:</span>
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('Y-m-d H:i:s') }}
                                                                    s/d
                                                                    {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('Y-m-d H:i:s') }}
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted">Status:</span>
                                                                    <span
                                                                        class="badge {{ $detail['status'] == 'Sedang Berlangsung' ? 'badge-success' : 'badge-secondary' }}">
                                                                        {{ $detail['status'] }}
                                                                    </span>
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted">Tim Auditor:</span>
                                                                    @include('shared.tim-auditor-display', [
                                                                        'timAuditorString' =>
                                                                            $detail['tim_auditor'],
                                                                        'timAuditorDetail' =>
                                                                            $detail['tim_auditor_detail'] ?? null,
                                                                    ])
                                                                </div>
                                                            @else
                                                                <div>
                                                                    <span class="text-muted">Status:</span>
                                                                    <span
                                                                        class="badge badge-warning">{{ $detail['status'] }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="mb-2">
                                                        <strong>{{ $detail['prodi'] }}:</strong>
                                                        @if (isset($detail['jadwal']) && $detail['jadwal'])
                                                            <div>
                                                                <span class="text-muted">Jadwal:</span>
                                                                {{ Carbon::parse($detail['jadwal']['tanggal_mulai'])->format('Y-m-d H:i:s') }}
                                                                s/d
                                                                {{ Carbon::parse($detail['jadwal']['tanggal_selesai'])->format('Y-m-d H:i:s') }}
                                                            </div>
                                                            <div>
                                                                <span class="text-muted">Status:</span>
                                                                <span
                                                                    class="badge {{ $detail['status'] == 'Sedang Berlangsung' ? 'badge-success' : 'badge-secondary' }}">
                                                                    {{ $detail['status'] }}
                                                                </span>
                                                            </div>
                                                            @if (Auth::user()->hasActiveRole('Super Admin') ||
                                                                    Auth::user()->hasActiveRole('Admin LPM') ||
                                                                    Auth::user()->hasActiveRole('Auditor'))
                                                                <div>
                                                                    <span class="text-muted">Tim Auditor:</span>
                                                                    @include('shared.tim-auditor-display', [
                                                                        'timAuditorString' =>
                                                                            $detail['tim_auditor'],
                                                                        'timAuditorDetail' =>
                                                                            $detail['tim_auditor_detail'] ?? null,
                                                                    ])
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
                                                $hasAssignedJadwal = false;
                                                $matchedProdi = '';

                                                foreach ($item->filtered_details as $detail) {
                                                    if (isset($detail['jadwal'])) {
                                                        if (str_contains($detail['tim_auditor'], Auth::user()->name)) {
                                                            $hasAssignedJadwal = true;
                                                            $matchedProdi = $detail['prodi'];
                                                            break;
                                                        }
                                                    }
                                                }
                                            @endphp

                                            @if ($hasAssignedJadwal)
                                                <a href="{{ route('pemenuhan-dokumen.showGroup', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> DETAIL
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Bukan penugasan Anda</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Admin Prodi'))
                                            @php
                                                $userProdi = getActiveProdi();
                                                $kodeProdi = trim(explode('-', $userProdi)[0]);
                                                $hasMatchingProdi = false;
                                                $matchedProdi = '';
                                                $prodiStatus = 'Belum ada jadwal'; // Default status

                                                foreach ($item->filtered_details as $detail) {
                                                    if (str_contains($detail['prodi'], $kodeProdi)) {
                                                        $hasMatchingProdi = true;
                                                        $matchedProdi = $detail['prodi'];
                                                        $prodiStatus = $detail['status']; // Ambil status dari detail
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            @if ($hasMatchingProdi)
                                                <!-- Button dengan validasi status untuk Admin Prodi -->
                                                <button type="button" class="btn btn-info btn-sm"
                                                    onclick="checkJadwalStatus(
                                                        {{ $item->lembaga_akreditasi_id }}, 
                                                        {{ $item->jenjang_id }}, 
                                                        '{{ $prodiStatus }}', 
                                                        'Admin Prodi',
                                                        {
                                                            status: '{{ request('status') }}',
                                                            year: '{{ request('year') }}',
                                                            jenjang: '{{ request('jenjang') }}'
                                                        }
                                                    )">
                                                    <i class="fas fa-eye"></i> DETAIL
                                                </button>
                                            @else
                                                <span class="badge badge-secondary">Bukan program studi Anda</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Fakultas'))
                                            @php
                                                $userFakultas = getActiveFakultas();
                                                $hasMatchingProdi = false;
                                                $matchedProdi = [];

                                                foreach ($item->filtered_details as $detail) {
                                                    $detailFakultas = isset($detail['fakultas'])
                                                        ? $detail['fakultas']
                                                        : '';
                                                    if ($detailFakultas == $userFakultas) {
                                                        $hasMatchingProdi = true;
                                                        $matchedProdi[] = $detail['prodi'];
                                                    }
                                                }
                                                $prodiList = implode(', ', $matchedProdi);
                                            @endphp

                                            @if ($hasMatchingProdi)
                                                <a href="{{ route('pemenuhan-dokumen.showGroup', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm" title="{{ $prodiList }}">
                                                    <i class="fas fa-eye"></i> {{ $prodiList }}
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Bukan fakultas Anda</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Admin PPG'))
                                            <a href="{{ route('pemenuhan-dokumen.showGroup', [
                                                'lembagaId' => $item->lembaga_akreditasi_id,
                                                'jenjangId' => $item->jenjang_id,
                                                'status' => request('status'),
                                                'year' => request('year'),
                                                'jenjang' => request('jenjang'),
                                            ]) }}"
                                                class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> DETAIL
                                            </a>
                                        @elseif(Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM'))
                                            @php
                                                $prodiList = $item->filtered_details->pluck('prodi')->join(', ');
                                            @endphp
                                            <a href="{{ route('pemenuhan-dokumen.showGroup', [
                                                'lembagaId' => $item->lembaga_akreditasi_id,
                                                'jenjangId' => $item->jenjang_id,
                                                'status' => request('status'),
                                                'year' => request('year'),
                                                'jenjang' => request('jenjang'),
                                            ]) }}"
                                                class="btn btn-info btn-sm" title="{{ $prodiList }}">
                                                <i class="fas fa-eye"></i> {{ $prodiList }}
                                            </a>
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
    <!-- SweetAlert2 CDN -->
    <style>
        /* Tebalkan border tabel */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Script untuk validasi status jadwal sebelum akses detail
        function checkJadwalStatus(lembagaId, jenjangId, status, userRole, params = {}) {
            // Daftar status yang tidak diizinkan untuk akses (sesuai StatusService)
            const blockedStatuses = ['Belum Dimulai', 'Belum ada jadwal'];

            // Khusus untuk Admin Prodi, block jika status belum dimulai
            if (userRole === 'Admin Prodi' && blockedStatuses.includes(status)) {
                let alertMessage = '';
                let alertTitle = '';

                if (status === 'Belum Dimulai') {
                    alertTitle = 'Jadwal Belum Dimulai';
                    alertMessage =
                        'Anda belum dapat mengakses detail dokumen karena jadwal AMI belum dimulai. Silakan tunggu hingga jadwal dimulai.';
                } else if (status === 'Belum ada jadwal') {
                    alertTitle = 'Belum Ada Jadwal';
                    alertMessage =
                        'Anda belum dapat mengakses detail dokumen karena belum ada jadwal AMI yang ditetapkan untuk program studi Anda.';
                }

                // Gunakan SweetAlert2 untuk notifikasi
                Swal.fire({
                    icon: 'warning',
                    title: alertTitle,
                    text: alertMessage,
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#3085d6'
                });
                return false; // Block akses
            }

            // Untuk role lain atau status yang diizinkan, lanjutkan ke halaman detail
            const queryParams = new URLSearchParams();

            // Tambahkan parameter filter yang ada
            if (params.status && params.status !== 'all') {
                queryParams.append('status', params.status);
            }
            if (params.year && params.year !== 'all') {
                queryParams.append('year', params.year);
            }
            if (params.jenjang && params.jenjang !== 'all') {
                queryParams.append('jenjang', params.jenjang);
            }

            // Konstruksi URL dengan parameter
            const baseUrl = `/pemenuhan-dokumen/${lembagaId}/${jenjangId}/group`;
            const finalUrl = queryParams.toString() ? `${baseUrl}?${queryParams.toString()}` : baseUrl;

            // Redirect ke halaman detail
            window.location.href = finalUrl;
            return true;
        }
    </script>
@endsection
