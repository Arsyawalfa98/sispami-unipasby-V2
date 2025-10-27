@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Permissions Management') }}</h1>
        <x-create-button route="permissions" title="Permission" />
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
            <h6 class="m-0 font-weight-bold text-primary">Permissions List</h6>
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
                            <input type="text" name="search" class="form-control" placeholder="Search by permission name..." 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
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
                            <th>Used In Roles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $permission)
                            <tr>
                                <td>{{ $permission->name }}</td>
                                <td>
                                    @foreach($permission->roles as $role)
                                        <span class="badge badge-info">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <x-action-buttons route="permissions" :id="$permission->id" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No permissions found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $permissions->withQueryString()->links() }}
        </div>
    </div>
@endsection

@push('css')
<style>
.badge {
    margin-right: 4px;
    margin-bottom: 4px;
}
.input-group .btn {
    margin-left: 4px;
}
</style>
@endpush