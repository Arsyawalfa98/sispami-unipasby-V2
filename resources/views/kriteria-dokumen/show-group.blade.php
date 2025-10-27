@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Kriteria Dokumen {{ $lembaga->nama }} {{ $jenjang->nama }} {{ $lembaga->tahun }}
        </h1>
        <div>
            <x-create-button 
                route="kriteria-dokumen" 
                title="Kriteria Detail"
                suffix="createDetail"
                :params="['lembagaId' => $lembaga->id, 'jenjangId' => $jenjang->id]"
            />
            <a href="{{ route('kriteria-dokumen.index') }}" class="btn btn-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @foreach ($kriteriaDokumen as $judul => $items)
        @php
            // Filter items yang memiliki data lengkap
            $filledItems = $items->filter(function ($item) {
                return $item->kode &&
                    $item->element &&
                    $item->indikator &&
                    $item->informasi &&
                    $item->kebutuhan_dokumen;
            });
        @endphp

        @if ($filledItems->isNotEmpty())
            <div class="card shadow mb-4">
                <div class="card-header bg-primary">
                    <h6 class="m-0 font-weight-bold text-white">{{ $judul }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Kode</th>
                                    <th>Element</th>
                                    <th>Indikator</th>
                                    <th>Capaian</th>
                                    <th>Kebutuhan Dokumen</th>
                                    <th>Bobot</th>
                                    <th>Kelola Kebutuhan</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filledItems as $item)
                                    <tr>
                                        <td>{{ $item->kode }}</td>
                                        <td>{{ $item->element }}</td>
                                        <td>{{ $item->indikator }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-info ml-2 info-btn"
                                                data-info="{{ $item->informasi }}" data-indikator="{{ $item->informasi }}">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </td>
                                        <td>{{ $item->kebutuhan_dokumen }}</td>
                                        {{-- <td>{{ $item->bobot }}</td> --}}
                                        <!-- Diubah menjadi -->
                                        <td>
                                            @if(empty($item->bobot) || $item->bobot == 0)
                                                <span class="text-danger">Bobot belum diisi, silahkan klik edit</span>
                                            @else
                                                {{ $item->bobot }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (auth()->user()->hasPermission('view-kelola-kebutuhan-kriteria-dokumen'))
                                                <a href="{{ route('kelola-kebutuhan-kriteria-dokumen.index', ['kriteriaDokumenId' => $item->id]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-cogs"></i> Kelola Kebutuhan
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            <x-action-buttons route="kriteria-dokumen" :id="$item->id"
                                                permission="kriteria-dokumen" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    @if ($kriteriaDokumen->isEmpty())
        <div class="alert alert-info">
            Belum ada data kriteria detail. Silakan tambahkan data baru.
        </div>
    @endif
    <!-- Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="infoModalLabel">Informasi Penilaian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="font-weight-bold mb-3">ISI INFORMASI:</h6>
                    <p id="modalIndikator"></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('.info-btn').click(function() {
                const info = $(this).data('info');
                const indikator = $(this).data('indikator');

                $('#modalIndikator').text(indikator);
                $('#infoModal').modal('show');
            });
        });
    </script>
@endpush

@push('css')
    <style>
        .modal-header {
            background-color: #f6c23e !important;
        }

        .modal-title {
            color: #000 !important;
        }
    </style>
@endpush
