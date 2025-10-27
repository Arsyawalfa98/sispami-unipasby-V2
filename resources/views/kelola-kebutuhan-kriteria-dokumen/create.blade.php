@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Kebutuhan Dokumen</h1>
        <a href="{{ route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => session('current_kriteria_dokumen_id')]) }}" 
           class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('kelola-kebutuhan-kriteria-dokumen.store') }}">
                @csrf
                <input type="hidden" name="kriteria_dokumen_id" value="{{ session('current_kriteria_dokumen_id') }}">

                <div class="form-group">
                    <label for="nama_dokumen">Nama Dokumen</label>
                    <input type="text" name="nama_dokumen" class="form-control @error('nama_dokumen') is-invalid @enderror" 
                           value="{{ old('nama_dokumen') }}" required>
                    @error('nama_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="tipe_dokumen">Tipe Dokumen</label>
                    <select name="tipe_dokumen" class="form-control @error('tipe_dokumen') is-invalid @enderror" required>
                        <option value="">Pilih Tipe Dokumen</option>
                        @foreach($tipeDokumen as $id => $nama)
                            <option value="{{ $id }}" {{ old('tipe_dokumen') == $id ? 'selected' : '' }}>
                                {{ $nama }}
                            </option>
                        @endforeach
                    </select>
                    
                    @error('tipe_dokumen')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan</label>
                    <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" 
                              rows="3">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="{{ route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => session('current_kriteria_dokumen_id')]) }}" 
                   class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
@endsection