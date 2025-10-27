@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Kriteria Dokumen</h1>
        <a href="{{ route('kriteria-dokumen.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if($lembagaAkreditasi->count() == 0)
                <div class="alert alert-info">
                    Tidak ada lembaga akreditasi yang tersedia untuk program studi dan fakultas Anda.
                </div>
                <a href="{{ route('kriteria-dokumen.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            @else
                <form method="POST" action="{{ route('kriteria-dokumen.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lembaga_akreditasi_id">Lembaga Akreditasi</label>
                                <select name="lembaga_akreditasi_id" class="form-control @error('lembaga_akreditasi_id') is-invalid @enderror" required>
                                    <option value="">Pilih Lembaga Akreditasi</option>
                                    @foreach($lembagaAkreditasi as $lembaga)
                                        <option value="{{ $lembaga->id }}" {{ old('lembaga_akreditasi_id') == $lembaga->id ? 'selected' : '' }}>
                                            {{ $lembaga->nama }} - {{ $lembaga->tahun }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lembaga_akreditasi_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jenjang_id">Jenjang</label>
                                <select name="jenjang_id" class="form-control @error('jenjang_id') is-invalid @enderror" required>
                                    <option value="">Pilih Jenjang</option>
                                    @foreach($jenjang as $j)
                                        <option value="{{ $j->id }}" {{ old('jenjang_id') == $j->id ? 'selected' : '' }}>
                                            {{ $j->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jenjang_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="{{ route('kriteria-dokumen.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </form>
            @endif
        </div>
    </div>
@endsection