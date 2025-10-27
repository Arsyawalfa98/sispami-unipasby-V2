@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Kriteria Dokumen</h1>
        <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id, 'jenjangId' => $kriteriaDokumen->jenjang_id]) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('kriteria-dokumen.update', $kriteriaDokumen->id) }}">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Lembaga Akreditasi</label>
                            <input type="text" class="form-control" value="{{ $kriteriaDokumen->lembagaAkreditasi->nama }}" readonly>
                            <input type="hidden" name="lembaga_akreditasi_id" value="{{ $kriteriaDokumen->lembaga_akreditasi_id }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Jenjang</label>
                            <input type="text" class="form-control" value="{{ $kriteriaDokumen->jenjang->nama }}" readonly>
                            <input type="hidden" name="jenjang_id" value="{{ $kriteriaDokumen->jenjang_id }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="judul_kriteria_dokumen_id">Judul Kriteria</label>
                            <input type="text" class="form-control" value="{{ $kriteriaDokumen->judulKriteriaDokumen->nama_kriteria_dokumen }}" readonly>
                            <input type="hidden" name="judul_kriteria_dokumen_id" value="{{ $kriteriaDokumen->judul_kriteria_dokumen_id }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kode">Kode</label>
                    <input type="text" class="form-control @error('kode') is-invalid @enderror" 
                           id="kode" name="kode" value="{{ old('kode', $kriteriaDokumen->kode) }}" required>
                    @error('kode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="element">Element</label>
                    <textarea class="form-control @error('element') is-invalid @enderror" 
                              id="element" name="element" rows="3" required>{{ old('element', $kriteriaDokumen->element) }}</textarea>
                    @error('element')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="indikator">Indikator</label>
                    <textarea class="form-control @error('indikator') is-invalid @enderror" 
                              id="indikator" name="indikator" rows="3" required>{{ old('indikator', $kriteriaDokumen->indikator) }}</textarea>
                    @error('indikator')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="informasi">Informasi</label>
                    <textarea class="form-control @error('informasi') is-invalid @enderror" 
                              id="informasi" name="informasi" rows="3" required>{{ old('informasi', $kriteriaDokumen->informasi) }}</textarea>
                    @error('informasi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="bobot">Bobot</span></label>
                    <input type="number" class="form-control" id="bobot" name="bobot" value="{{ old('bobot', $kriteriaDokumen->bobot) }}" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="kebutuhan_dokumen">Jumlah Upload Kebutuhan Dokumen</label>
                    <input type="number" class="form-control @error('kebutuhan_dokumen') is-invalid @enderror" 
                           id="kebutuhan_dokumen" name="kebutuhan_dokumen" 
                           value="{{ old('kebutuhan_dokumen', $kriteriaDokumen->kebutuhan_dokumen) }}" required>
                    @error('kebutuhan_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id, 'jenjangId' => $kriteriaDokumen->jenjang_id]) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
@endsection