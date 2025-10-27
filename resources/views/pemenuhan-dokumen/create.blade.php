@extends('layouts.admin')

@section('main-content')
<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            {{ $kriteriaDokumen->lembagaAkreditasi->nama }} - {{ $kriteriaDokumen->jenjang->nama }}
        </div>
        <div class="card-body">
            <div class="mb-4">
                <p>Batas Maksimal Dokumen: {{ $kriteriaDokumen->kebutuhan_dokumen }} | 
                   Dokumen Tersedia: {{ $kriteriaDokumen->capaian_dokumen ?? 0 }}</p>
            </div>

            <table class="table">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>No</th>
                        <th>Nama Dokumen</th>
                        <th>Tipe Dokumen</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kebutuhanDokumen as $index => $dokumen)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $dokumen->nama_dokumen }}</td>
                        <td>{{ $dokumen->tipe_dokumen }}</td>
                        <td>{{ $dokumen->keterangan }}</td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <form action="{{ route('pemenuhan-dokumen.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                @csrf
                <input type="hidden" name="kriteria_dokumen_id" value="{{ $kriteriaDokumen->id }}">
                
                <div class="form-group mb-3">
                    <label>Nama Dokumen</label>
                    <select name="nama_dokumen" class="form-control" required>
                        @foreach($kebutuhanDokumen as $dokumen)
                            <option value="{{ $dokumen->nama_dokumen }}">{{ $dokumen->nama_dokumen }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>File</label>
                    <input type="file" name="file" class="form-control" required>
                </div>

                <div class="form-group mb-3">
                    <label>Tambahan Informasi</label>
                    <textarea name="tambahan_informasi" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Upload Dokumen</button>
            </form>
        </div>
    </div>
</div>
@endsection