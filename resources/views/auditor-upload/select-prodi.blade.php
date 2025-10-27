@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Pilih Program Studi</h1>
            <p class="mb-0 text-muted">
                {{ $headerData->lembagaAkreditasi->nama ?? '' }} - {{ $headerData->jenjang->nama ?? '' }} - {{ $headerData->periode_atau_tahun ?? '' }}
            </p>
        </div>
        <a href="{{ route('auditor-upload.index', $filterParams) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Dropdown Selection untuk Prodi --}}
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-university"></i> Pilih Program Studi untuk Dikelola
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Terdapat <strong>{{ $prodiList->count() }}</strong> program studi untuk grup ini.
                        Silakan pilih salah satu untuk melanjutkan ke halaman upload.
                    </div>

                    <form method="GET" action="{{ route('auditor-upload.showGroupByLembaga', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}">
                        
                        @foreach($filterParams as $key => $value)
                            @if($value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <div class="form-group">
                            <label for="prodi_select" class="font-weight-bold">Program Studi:</label>
                            <select name="prodi" id="prodi_select" class="form-control form-control-lg" required>
                                <option value="">-- Pilih Program Studi --</option>
                                @foreach($prodiList as $prodi)
                                    <option value="{{ $prodi['prodi'] }}">
                                        {{ $prodi['prodi'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-arrow-right"></i> Lanjutkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
