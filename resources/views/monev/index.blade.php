{{-- resources/views/monev-kts/index.blade.php --}}
@extends('layouts.admin')

@php
    use Carbon\Carbon;
@endphp

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Monitoring Evaluasi Ketidaksesuaian (KTS)</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('monev-kts.index') }}" class="mb-0">
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
                        <a href="{{ route('monev-kts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 py-2">
                            <small><i class="fas fa-info-circle"></i>
                                Menampilkan data kriteria yang memiliki temuan <strong>Ketidaksesuaian (KTS)</strong> saja
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">* Gunakan filter di atas untuk mempermudah pencarian data</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            @if ($kriteriaDokumen->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Tidak ada data temuan ketidaksesuaian (KTS) yang tersedia untuk periode dan filter yang dipilih.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-warning text-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th>Lembaga Akreditasi</th>
                                <th>Periode/Tahun</th>
                                <th>Jenjang</th>
                                <th>Status & Tim Auditor</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        {{-- PERBAIKAN: Bagian tabel untuk menampilkan informasi KTS yang benar --}}
                        <tbody>
                            @foreach ($kriteriaDokumen as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    {{-- PERBAIKAN: Hapus informasi jumlah KTS dari kolom Lembaga Akreditasi --}}
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
                                                            {{-- PERBAIKAN: Tampilkan jumlah KTS di bagian prodi --}}
                                                            <strong>{{ $detail['prodi'] }}
                                                                @if (isset($detail['jumlah_temuan']) && $detail['jumlah_temuan'] > 0)
                                                                    <span
                                                                        class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-danger' }}">
                                                                        {{ $detail['jumlah_temuan'] }}
                                                                        {{ $statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS' }}
                                                                    </span>
                                                                @endif
                                                                :
                                                            </strong>

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
                                                            {{-- PERBAIKAN: Tampilkan jumlah KTS di bagian prodi --}}
                                                            <strong>{{ $detail['prodi'] }}
                                                                @if (isset($detail['jumlah_temuan']) && $detail['jumlah_temuan'] > 0)
                                                                    <span
                                                                        class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-danger' }}">
                                                                        {{ $detail['jumlah_temuan'] }}
                                                                        {{ $statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS' }}
                                                                    </span>
                                                                @endif
                                                                :
                                                            </strong>

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
                                                        {{-- PERBAIKAN: Tampilkan jumlah KTS di bagian prodi --}}
                                                        <strong>{{ $detail['prodi'] }}
                                                            @if (isset($detail['jumlah_temuan']) && $detail['jumlah_temuan'] > 0)
                                                                <span
                                                                    class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-danger' }}">
                                                                    {{ $detail['jumlah_temuan'] }}
                                                                    {{ $statusTemuan === 'TERCAPAI' ? 'TERCAPAI' : 'KTS' }}
                                                                </span>
                                                            @endif
                                                            :
                                                        </strong>

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
                                        @php
                                            // Dynamic property check berdasarkan status_temuan
                                            if ($statusTemuan === 'KETIDAKSESUAIAN') {
                                                $hasTemuan = $item->has_kts_in_group ?? false;
                                                $totalTemuan = $item->total_kts_in_group ?? 0;
                                                $routeName = 'monev-kts.showGroup';
                                                $btnClass = 'btn-warning';
                                                $icon = 'fa-exclamation-triangle';
                                                $label = 'DETAIL KTS';
                                                $badgeClass = 'badge-danger';
                                            } else {
                                                // TERCAPAI
                                                $hasTemuan = $item->has_tercapai_in_group ?? false;
                                                $totalTemuan = $item->total_tercapai_in_group ?? 0;
                                                $routeName = 'monev-tercapai.showGroup';
                                                $btnClass = 'btn-success';
                                                $icon = 'fa-check-circle';
                                                $label = 'DETAIL TERCAPAI';
                                                $badgeClass = 'badge-light';
                                            }
                                        @endphp

                                        @if ($hasTemuan)
                                            <a href="{{ route($routeName, [
                                                'lembagaId' => $item->lembaga_akreditasi_id,
                                                'jenjangId' => $item->jenjang_id,
                                            ]) }}"
                                                class="btn {{ $btnClass }} btn-sm">
                                                <i class="fas {{ $icon }}"></i> {{ $label }}
                                                <span class="badge {{ $badgeClass }} ml-1">{{ $totalTemuan }}</span>
                                            </a>
                                        @else
                                            @if ($statusTemuan === 'KETIDAKSESUAIAN')
                                                <span class="badge badge-success">Tidak ada temuan KTS</span>
                                            @else
                                                <span class="badge badge-secondary">Tidak ada temuan tercapai</span>
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

    <style>
        /* Tebalkan border tabel */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }

        /* Style khusus untuk header warning */
        .bg-warning {
            background-color: #ffc107 !important;
        }

        /* Style untuk card detail KTS */
        .bg-light {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107;
        }
    </style>

    <!-- SweetAlert2 untuk notifikasi -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
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
    </script>
@endsection
