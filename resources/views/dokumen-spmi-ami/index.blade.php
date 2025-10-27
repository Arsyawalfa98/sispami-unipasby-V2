@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Dokumen SPMI & AMI') }}</h1>
        <x-create-button route="dokumen-spmi-ami" title="Dokumen" />
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Dokumen</h6>
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <form id="search-form" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari dokumen..." 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <a href="{{ route('dokumen-spmi-ami.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-sync"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>No.</th>
                            <th>Kategori Dokumen</th>
                            <th>Nama Dokumen</th>
                            <th>Role Akses</th> {{-- penambahan rolenya --}}
                            <th>File</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dokumens as $dokumen)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $dokumen->kategori_dokumen }}</td>
                                <td>{{ $dokumen->nama_dokumen }}</td>
                                <td>
                                    {{-- penambahan rolenya --}}
                                    @if($dokumen->kategori)
                                        @foreach($dokumen->kategori->roles as $role)
                                            <span class="badge badge-info">{{ $role->name }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ Storage::url($dokumen->file_path) }}" 
                                       class="btn btn-sm btn-warning"
                                       target="_blank">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </td>
                                <td>
                                    @if($dokumen->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-danger">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <x-action-buttons route="dokumen-spmi-ami" :id="$dokumen->id" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada dokumen</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $dokumens->withQueryString()->links() }}
        </div>
    </div>
@endsection

@push('css')
<style>
.badge {
    margin-right: 4px;
}
.input-group .btn {
    margin-left: 4px;
}
.fa, .fas {
    margin-right: 5px;
}
</style>
@endpush