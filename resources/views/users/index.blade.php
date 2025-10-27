@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Users Management') }}</h1>
        <div>
            <x-integrate-button route="users" title="User" />
            <x-create-button route="users" title="User" />
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Users List</h6>
        </div>
        <div class="card-body">
            <!-- Search and Filter Form -->
            <form id="search-form" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <select name="role" class="form-control" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by name or username..." 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
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
                            <th>Name</th>
                            <th>Username</th>
                            <th>Jabatan</th>
                            <th>Prodi</th>
                            <th>Fakultas</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->jabatan ?: '-' }}</td>
                                <td>{{ $user->prodi ? trim(explode('-', $user->prodi)[1] ?? '') : '-' }}</td>
                                <td>{{ $user->fakultas ? trim(explode('-', $user->fakultas)[1] ?? '') : '-' }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge badge-info">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <x-action-buttons route="users" :id="$user->id" />
                                    
                                    @if(Auth::user()->hasActiveRole('Super Admin'))
                                        <a href="{{ route('users.login-as', $user->id) }}" class="btn btn-sm btn-warning" 
                                        title="Login As" onclick="return confirm('Yakin ingin login sebagai {{ $user->name }}?')">
                                            <i class="fas fa-user-secret"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No users found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $users->withQueryString()->links() }}
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
</style>
@endpush