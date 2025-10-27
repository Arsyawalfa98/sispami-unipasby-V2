@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Judul Kriteria Dokumen</h1>
        <x-create-button route="judul-kriteria-dokumen" title="Judul Kriteria Dokumen" />
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('judul-kriteria-dokumen.index') }}" class="mb-0">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari Nama Kriteria Dokumen..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                        <a href="{{ route('judul-kriteria-dokumen.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
             <!-- Note dengan ukuran kecil dan warna merah -->
            <div class="alert alert-danger py-2 mb-3" style="font-size: 0.875rem;">
                <strong>*Note:</strong> Lakukan pengecekan data dalam tabel berikut terlebih dahulu. Pastikan tidak ada nama yang sama.
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Kriteria Dokumen</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($judulKriteriaDokumen as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->nama_kriteria_dokumen }}</td>
                                <td>
                                    <x-action-buttons 
                                        route="judul-kriteria-dokumen" 
                                        :id="$item->id"
                                        permission="judul-kriteria-dokumen"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $judulKriteriaDokumen->links() }}
        </div>
    </div>
@endsection