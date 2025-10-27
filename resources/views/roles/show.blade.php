@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Role Detail</h1>
            <a href="{{ route('roles.index') }}" class="btn btn-sm btn-primary shadow-sm">
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
                            <p>{{ $role->name }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Created At:</strong>
                            <p>{{ $role->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>Permissions</h5>
                        <hr>
                        <div class="mb-3">
                            @foreach($role->permissions as $permission)
                                <span class="badge badge-info">{{ $permission->name }}</span>
                            @endforeach
                        </div>

                        <h5 class="mt-4">Menu Access</h5>
                        <hr>
                        <div class="mb-3">
                            @foreach($role->menus as $menu)
                                <span class="badge badge-success">{{ $menu->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('edit-roles'))
                    <div class="mt-3">
                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Role
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Users with this Role</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($role->users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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