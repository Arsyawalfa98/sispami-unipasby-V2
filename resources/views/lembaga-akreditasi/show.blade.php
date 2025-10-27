@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Lembaga Akreditasi</h1>
        <a href="{{ route('lembaga-akreditasi.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px">Nama Lembaga</th>
                        <td>{{ $lembagaAkreditasi->nama }}</td>
                    </tr>
                    <tr>
                        <th>Program Studi</th>
                        <td>
                            @foreach($lembagaAkreditasi->details as $detail)
                                - {{ $detail->prodi }}<br>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>Fakultas</th>
                        <td>
                            @foreach($lembagaAkreditasi->details as $detail)
                                - {{ $detail->fakultas }}<br>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>Tahun</th>
                        <td>{{ $lembagaAkreditasi->tahun }}</td>
                    </tr>
                    <tr>
                        <th>Dibuat Pada</th>
                        <td>{{ $lembagaAkreditasi->created_at->format('d F Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Diupdate</th>
                        <td>{{ $lembagaAkreditasi->updated_at->format('d F Y H:i') }}</td>
                    </tr>
                </table>
            </div>

            <div class="mt-3">
                @can('edit-lembaga-akreditasi')
                    <a href="{{ route('lembaga-akreditasi.edit', $lembagaAkreditasi->id) }}" 
                       class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endcan
                
                @can('delete-lembaga-akreditasi')
                    <form action="{{ route('lembaga-akreditasi.destroy', $lembagaAkreditasi->id) }}" 
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