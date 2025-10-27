@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Tambah Detail Kriteria - {{ $lembaga->nama }} {{ $jenjang->nama }} {{ $lembaga->tahun }}
        </h1>
        <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $lembaga->id, 'jenjangId' => $jenjang->id]) }}" 
           class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('kriteria-dokumen.storeDetail', ['lembagaId' => $lembaga->id, 'jenjangId' => $jenjang->id]) }}">
                @csrf

                <div class="form-group">
                    <label for="judul_kriteria_dokumen_id">Judul Kriteria</label>
                    <select name="judul_kriteria_dokumen_id" class="form-control @error('judul_kriteria_dokumen_id') is-invalid @enderror" required>
                        <option value="">Pilih Judul Kriteria</option>
                        @foreach($judulKriteria as $judul)
                            <option value="{{ $judul->id }}" {{ old('judul_kriteria_dokumen_id') == $judul->id ? 'selected' : '' }}>
                                {{ $judul->nama_kriteria_dokumen }}
                            </option>
                        @endforeach
                    </select>
                    @error('judul_kriteria_dokumen_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="kode">Kode</label>
                    <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror" 
                           value="{{ old('kode') }}" required>
                    @error('kode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="element">Element</label>
                    <textarea name="element" class="form-control @error('element') is-invalid @enderror" 
                              rows="3" required>{{ old('element') }}</textarea>
                    @error('element')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="indikator">Indikator</label>
                    <textarea name="indikator" class="form-control @error('indikator') is-invalid @enderror" 
                              rows="3" required>{{ old('indikator') }}</textarea>
                    @error('indikator')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="informasi">Informasi</label>
                    <textarea name="informasi" class="form-control @error('informasi') is-invalid @enderror" 
                              rows="3" required>{{ old('informasi') }}</textarea>
                    @error('informasi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                {{-- <div class="form-group">
                    <label for="bobot">Bobot <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="bobot" name="bobot" value="{{ old('bobot', 0) }}" min="0" max="100" step="0.01" required>
                    <small class="form-text text-muted">Bobot untuk penilaian dokumen (0-100)</small>
                </div> --}}
                <div class="form-group">
                    <label for="kebutuhan_dokumen">Jumlah Upload Kebutuhan Dokumen</label>
                    <input type="number" name="kebutuhan_dokumen" class="form-control @error('kebutuhan_dokumen') is-invalid @enderror" 
                           value="{{ old('kebutuhan_dokumen') }}" required>
                    @error('kebutuhan_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $lembaga->id, 'jenjangId' => $jenjang->id]) }}" 
                   class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
@endsection