@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Jadwal AMI</h1>
        <a href="{{ route('jadwal-ami.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px">Program Studi</th>
                        <td>{{ $jadwalAmi->prodi ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Fakultas</th>
                        <td>{{ $jadwalAmi->fakultas }}</td>
                    </tr>
                    <tr>
                        <th>Standar Akreditasi</th>
                        <td>{{ $jadwalAmi->standar_akreditasi }}</td>
                    </tr>
                    <tr>
                        <th>Periode</th>
                        <td>{{ $jadwalAmi->periode }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Mulai</th>
                        <td>{{ \Carbon\Carbon::parse($jadwalAmi->tanggal_mulai)->format('d F Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Selesai</th>
                        <td>{{ \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai)->format('d F Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Tim Auditor</th>
                        <td>
                            @foreach($jadwalAmi->timAuditor as $auditor)
                                <div class="badge badge-primary mb-1">
                                    {{ $auditor->name }} ({{ $auditor->username }})
                                </div><br>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>Dibuat Pada</th>
                        <td>{{ $jadwalAmi->created_at->format('d F Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Diupdate</th>
                        <td>{{ $jadwalAmi->updated_at->format('d F Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>

            <div class="mt-3">
                @can('edit-jadwal-ami')
                    <a href="{{ route('jadwal-ami.edit', $jadwalAmi->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endcan
                
                @can('delete-jadwal-ami')
                    <form action="{{ route('jadwal-ami.destroy', $jadwalAmi->id) }}" 
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