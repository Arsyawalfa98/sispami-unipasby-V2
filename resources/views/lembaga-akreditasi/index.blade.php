@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Lembaga Akreditasi</h1>
        <x-create-button route="lembaga-akreditasi" title="Lembaga Akreditasi" />
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('lembaga-akreditasi.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari Nama Lembaga Akreditasi..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                        <a href="{{ route('lembaga-akreditasi.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Lembaga</th>
                            <th>Program Studi</th>
                            <th>Fakultas</th>
                            <th>Tahun</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lembagaAkreditasi as $lembaga)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $lembaga->nama }}</td>
                                <td>
                                    @foreach($lembaga->details as $detail)
                                        - {{ $detail->prodi }}<br>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($lembaga->details as $detail)
                                        - {{ $detail->fakultas }}<br>
                                    @endforeach
                                </td>
                                <td>{{ $lembaga->tahun }}</td>
                                <td>
                                    <x-action-buttons 
                                        route="lembaga-akreditasi" 
                                        :id="$lembaga->id" 
                                        permission="lembaga-akreditasi"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lembagaAkreditasi->links() }}
        </div>
    </div>
@endsection