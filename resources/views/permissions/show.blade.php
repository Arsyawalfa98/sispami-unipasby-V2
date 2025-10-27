@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Permission Detail</h1>
            <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-primary shadow-sm">
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
                            <p>{{ $permission->name }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Created At:</strong>
                            <p>{{ $permission->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>Roles with this Permission</h5>
                        <hr>
                        <div class="mb-3">
                            @foreach($permission->roles as $role)
                                <span class="badge badge-info">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('edit-permissions'))
                    <div class="mt-3">
                        <a href="{{ route('permissions.edit', $permission->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Permission
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Users with this Permission (via Roles)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permission->roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        @foreach($role->users as $user)
                                            <span class="badge badge-success">{{ $user->name }}</span>
                                        @endforeach
                                    </td>
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
