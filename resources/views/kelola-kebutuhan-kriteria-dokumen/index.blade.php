@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Tambah Upload Kebutuhan Dokumen {{ $kriteriaDokumen->judulKriteriaDokumen?->nama_kriteria_dokumen ?? 'Kriteria' }} {{ $kriteriaDokumen->periode_atau_tahun }}
        </h1>
        <div class="d-flex align-items-center gap-2">
            @if ($countDokumen < $maxDokumen)
                <x-create-button route="kelola-kebutuhan-kriteria-dokumen" title="Kebutuhan Dokumen" />
            @endif
            <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id, 'jenjangId' => $kriteriaDokumen->jenjang_id]) }}"
                class="btn btn-secondary btn-sm mb-3 ml-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div>
        <h6 class="text-primary">
            {{ $kriteriaDokumen->lembagaAkreditasi->nama }} - {{ $kriteriaDokumen->jenjang->nama }}
        </h6>
        <p class="text-muted">
            Batas Maksimal Dokumen: {{ $maxDokumen }} | Dokumen Tersedia: {{ $countDokumen }}
        </p>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if ($kelolaKebutuhan->isEmpty())
                <div class="alert alert-info">
                    Belum ada data kebutuhan dokumen. Silakan tambahkan data baru.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>No</th>
                                <th>Nama Dokumen</th>
                                <th>Tipe Dokumen</th>
                                <th>Keterangan</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kelolaKebutuhan as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->nama_dokumen }}</td>
                                    <td>{{ $item->tipeDokumen ? $item->tipeDokumen->nama : '-' }}</td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>
                                        <x-action-buttons route="kelola-kebutuhan-kriteria-dokumen" :id="$item->id" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('js')
    <script>
        // Delete confirm
        $('.delete-confirm').click(function(e) {
            e.preventDefault();
            const form = $(this).closest('form');

            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
