@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Detail Kategori</h1>
            <a href="{{ route('kategori-dokumen.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Basic Information</h5>
                        <hr>
                        <div class="mb-3">
                            <strong>Nama Kategori:</strong>
                            <p>{{ $kategori->nama_kategori }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Created At:</strong>
                            <p>{{ $kategori->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Role Access</h5>
                        <hr>
                        <div class="mb-3">
                            <strong>Roles:</strong>
                            <div>
                                @foreach($kategori->roles as $role)
                                    <span class="badge badge-primary">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @can('edit-kategori-dokumen')
                    <div class="mt-3">
                        <a href="{{ route('kategori-dokumen.edit', $kategori->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Kategori
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .badge { margin: 2px; }
</style>
@endpush