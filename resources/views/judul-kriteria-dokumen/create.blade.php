@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Judul Kriteria Dokumen</h1>
        <a href="{{ route('judul-kriteria-dokumen.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('judul-kriteria-dokumen.store') }}">
                @csrf
                <div class="form-group">
                    <label for="nama_kriteria_dokumen">Nama Kriteria Dokumen</label>
                    <input type="text" class="form-control @error('nama_kriteria_dokumen') is-invalid @enderror" 
                           id="nama_kriteria_dokumen" name="nama_kriteria_dokumen" value="{{ old('nama_kriteria_dokumen') }}" required>
                    @error('nama_kriteria_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="{{ route('judul-kriteria-dokumen.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
@endsection