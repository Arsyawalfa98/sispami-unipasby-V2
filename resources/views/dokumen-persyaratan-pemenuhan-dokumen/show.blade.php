@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Dokumen</h1>
        <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
            'kriteriaDokumenId' => $dokumen->kriteria_dokumen_id,
            'prodi' => $dokumen->prodi,
        ]) }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Informasi Dokumen</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Nama Dokumen</th>
                        <td>{{ $dokumen->nama_dokumen }}</td>
                    </tr>
                    <tr>
                        <th>File</th>
                        <td>
                            @if ($dokumen->file)
                                <a href="{{ Storage::url('pemenuhan_dokumen/'.$dokumen->file) }}" class="btn btn-sm btn-warning" target="_blank">
                                    <i class="fas fa-download"></i> Download File
                                </a>
                            @else
                                <span class="badge badge-danger">Tidak ada file</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Tambahan Informasi</th>
                        <td>{{ $dokumen->tambahan_informasi ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endsection
