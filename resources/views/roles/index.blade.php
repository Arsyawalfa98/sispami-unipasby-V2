@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Roles Management') }}</h1>
        <x-create-button route="roles" title="Role" />
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
            <h6 class="m-0 font-weight-bold text-primary">Roles List</h6>
        </div>
        <div class="card-body">
            <!-- Search and Filter Form -->
            <form id="search-form" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <select name="permission" class="form-control" onchange="this.form.submit()">
                            <option value="">All Permissions</option>
                            @foreach($permissions as $permission)
                                <option value="{{ $permission }}" {{ request('permission') == $permission ? 'selected' : '' }}>
                                    {{ $permission }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by role name..." 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
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
                            <th>Permissions</th>
                            <th>Menu Access</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>
                                    @foreach($role->permissions as $permission)
                                        <span class="badge badge-info">{{ $permission->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($role->menus as $menu)
                                        <span class="badge badge-success">{{ $menu->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <x-action-buttons route="roles" :id="$role->id" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No roles found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $roles->withQueryString()->links() }}
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