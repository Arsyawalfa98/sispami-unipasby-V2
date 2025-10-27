@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">User Detail</h1>
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary shadow-sm">
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
                            <strong>Name:</strong>
                            <p>{{ $user->name }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Username:</strong>
                            <p>{{ $user->username }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Email:</strong>
                            <p>{{ $user->email ?? '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <span class="badge badge-{{ $user->is_active ? 'success' : 'danger' }}">
                                {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Created At:</strong>
                            <p>{{ $user->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>Roles & Prodis</h5>
                        <hr>
                        <div class="mb-3">
                            <strong>Roles:</strong>
                            <div>
                                @foreach($user->roles as $role)
                                    <span class="badge badge-primary">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Prodis:</strong>
                            <div>
                                @if($user->prodis->isNotEmpty())
                                    @foreach($user->prodis as $prodi)
                                        <span class="badge badge-{{ $prodi->is_default ? 'success' : 'secondary' }}" title="{{ $prodi->nama_fakultas }}">
                                            {{ $prodi->kode_prodi }} - {{ $prodi->nama_prodi }}
                                            @if($prodi->is_default)
                                                <i class="fas fa-star fa-xs ml-1" title="Default"></i>
                                            @endif
                                        </span>
                                    @endforeach
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-info-circle"></i> Total: {{ $user->prodis->count() }} prodi
                                    </small>
                                @else
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Belum tersync
                                    </span>
                                    <small class="text-muted d-block mt-1">
                                        Prodi akan otomatis sync saat user login.
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('edit-users'))
                    <div class="mt-3">
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .badge {
        margin: 2px;
    }
</style>
@endpush