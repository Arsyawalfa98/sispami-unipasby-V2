{{-- resources/views/kelengkapan-dokumen/index.blade.php --}}
@extends('layouts.admin')

@php
    use Carbon\Carbon;
@endphp

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Form Kelengkapan Dokumen (Checklist)</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('kelengkapan-dokumen.index') }}" class="mb-0">
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
                        <a href="{{ route('kelengkapan-dokumen.index') }}" class="btn btn-secondary">
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
                    Tidak ada data kelengkapan dokumen yang tersedia.
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
                                                                        'badgeKetuaClass' => 'badge-success',
                                                                        'badgeAnggotaClass' => 'badge-info',
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
                                                @elseif (Auth::user()->hasActiveRole('Fakultas'))
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
                                                                        'badgeKetuaClass' => 'badge-warning',
                                                                        'badgeAnggotaClass' => 'badge-info',
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
                                                                        'badgeKetuaClass' => 'badge-success',
                                                                        'badgeAnggotaClass' => 'badge-info',
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
                                                        // EDIT: Cek dengan tim_auditor_detail jika tersedia
                                                        if (isset($detail['tim_auditor_detail'])) {
                                                            $isAssigned =
                                                                $detail['tim_auditor_detail']['ketua'] ===
                                                                    Auth::user()->name ||
                                                                in_array(
                                                                    Auth::user()->name,
                                                                    $detail['tim_auditor_detail']['anggota'],
                                                                );
                                                        } else {
                                                            // Fallback ke cara lama
                                                            $isAssigned = str_contains(
                                                                $detail['tim_auditor'],
                                                                Auth::user()->name,
                                                            );
                                                        }

                                                        if ($isAssigned) {
                                                            $hasAssignedJadwal = true;
                                                            $matchedProdi = $detail['prodi'];
                                                            break;
                                                        }
                                                    }
                                                }
                                            @endphp

                                            <!-- Selalu tampilkan tombol DETAIL untuk Auditor yang ditugaskan, tanpa pengecekan jadwal -->
                                            @if ($hasAssignedJadwal)
                                                <a href="{{ route('kelengkapan-dokumen.showGroup', [
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
                                                $kodeProdi = trim(explode('-', $userProdi)[0]);
                                                $hasMatchingProdi = false;
                                                $matchedProdi = '';

                                                foreach ($item->filtered_details as $detail) {
                                                    if (str_starts_with($detail['prodi'], $kodeProdi)) {
                                                        $hasMatchingProdi = true;
                                                        $matchedProdi = $detail['prodi'];
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            <!-- Selalu tampilkan tombol DETAIL untuk Admin tanpa pengecekan jadwal -->
                                            @if ($hasMatchingProdi)
                                                <a href="{{ route('kelengkapan-dokumen.showGroup', [
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
                                                <a href="{{ route('kelengkapan-dokumen.showGroup', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm" title="{{ $prodiList }}">
                                                    <i class="fas fa-eye"></i> DETAIL
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Bukan fakultas Anda</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Admin PPG'))
                                            @php
                                                $hasMatchingProdi = false;
                                                $matchedProdi = [];

                                                // Admin PPG sudah difilter di repository, tapi kita tetap bisa menampilkan info
                                                foreach ($item->filtered_details as $detail) {
                                                    $hasMatchingProdi = true;
                                                    $matchedProdi[] = $detail['prodi'];
                                                }
                                                $prodiList = implode(', ', $matchedProdi);
                                            @endphp

                                            @if ($hasMatchingProdi)
                                                <a href="{{ route('kelengkapan-dokumen.showGroup', [
                                                    'lembagaId' => $item->lembaga_akreditasi_id,
                                                    'jenjangId' => $item->jenjang_id,
                                                    'status' => request('status'),
                                                    'year' => request('year'),
                                                    'jenjang' => request('jenjang'),
                                                ]) }}"
                                                    class="btn btn-info btn-sm" title="{{ $prodiList }}">
                                                    <i class="fas fa-eye"></i> DETAIL
                                                </a>
                                            @else
                                                <span class="badge badge-secondary">Tidak ada data PPG</span>
                                            @endif
                                        @elseif(Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM'))
                                            @php
                                                $prodiList = $item->filtered_details->pluck('prodi')->join(', ');
                                            @endphp
                                            <a href="{{ route('kelengkapan-dokumen.showGroup', [
                                                'lembagaId' => $item->lembaga_akreditasi_id,
                                                'jenjangId' => $item->jenjang_id,
                                                'status' => request('status'),
                                                'year' => request('year'),
                                                'jenjang' => request('jenjang'),
                                            ]) }}"
                                                class="btn btn-info btn-sm" title="{{ $prodiList }}">
                                                <i class="fas fa-eye"></i> DETAIL
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
    <style>
        /* Tebalkan border tabel */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
    </style>
@endsection
