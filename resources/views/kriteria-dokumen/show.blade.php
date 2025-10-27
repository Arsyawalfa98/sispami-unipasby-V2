@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Kriteria Dokumen</h1>
        <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id, 'jenjangId' => $kriteriaDokumen->jenjang_id]) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th>Daftar Tipe Dokumen</th>
                        <td>
                            @if($kriteriaDokumen->kebutuhanDokumen->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Dokumen</th>
                                                <th>Tipe Dokumen</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kriteriaDokumen->kebutuhanDokumen as $index => $kebutuhan)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $kebutuhan->nama_dokumen }}</td>
                                                    <td>{{ $kebutuhan->tipeDokumen->nama ?? '-' }}</td>
                                                    <td>{{ $kebutuhan->keterangan ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <span class="text-muted">Belum ada data tipe dokumen</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th style="width: 200px">Lembaga Akreditasi</th>
                        <td>{{ $kriteriaDokumen->lembagaAkreditasi->nama }}</td>
                    </tr>
                    <tr>
                        <th>Jenjang</th>
                        <td>{{ $kriteriaDokumen->jenjang->nama }}</td>
                    </tr>
                    <tr>
                        <th>Judul Kriteria</th>
                        <td>{{ $kriteriaDokumen->judulKriteriaDokumen->nama_kriteria_dokumen }}</td>
                    </tr>
                    <tr>
                        <th>Kode</th>
                        <td>{{ $kriteriaDokumen->kode }}</td>
                    </tr>
                    <tr>
                        <th>Element</th>
                        <td>{{ $kriteriaDokumen->element }}</td>
                    </tr>
                    <tr>
                        <th>Indikator</th>
                        <td>{{ $kriteriaDokumen->indikator }}</td>
                    </tr>
                    <tr>
                        <th>Capaian</th>
                        <td>{{ $kriteriaDokumen->informasi }}</td>
                    </tr>
                    <tr>
                        <th>Bobot</th>
                        <td>{{ $kriteriaDokumen->bobot }}</td>
                    </tr>
                    <tr>
                        <th>Kebutuhan Dokumen</th>
                        <td>{{ $kriteriaDokumen->kebutuhan_dokumen }}</td>
                    </tr>
                    <tr>
                        <th>Dibuat Pada</th>
                        <td>{{ $kriteriaDokumen->created_at->format('d F Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Diupdate</th>
                        <td>{{ $kriteriaDokumen->updated_at->format('d F Y H:i') }}</td>
                    </tr>
                </table>
            </div>

            <div class="mt-3">
                @can('edit-kriteria-dokumen')
                    <a href="{{ route('kriteria-dokumen.edit', $kriteriaDokumen->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endcan
                
                @can('delete-kriteria-dokumen')
                    <form action="{{ route('kriteria-dokumen.destroy', $kriteriaDokumen->id) }}" 
                          method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger delete-confirm">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
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