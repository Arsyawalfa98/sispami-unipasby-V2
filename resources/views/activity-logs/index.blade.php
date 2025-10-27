@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Activity Logs</h1>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Activity List</h6>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <input type="text" name="user" class="form-control" placeholder="Search by username..."
                                   value="{{ request('user') }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <select name="action" class="form-control">
                                <option value="">All Actions</option>
                                <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                                <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                                <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <select name="module" class="form-control">
                                <option value="">All Modules</option>
                                <option value="users" {{ request('module') == 'users' ? 'selected' : '' }}>Users</option>
                                <option value="roles" {{ request('module') == 'roles' ? 'selected' : '' }}>Roles</option>
                                <option value="permissions" {{ request('module') == 'permissions' ? 'selected' : '' }}>Permissions</option>
                                <option value="menus" {{ request('module') == 'menus' ? 'selected' : '' }}>Menus</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="date" name="date_start" class="form-control" value="{{ request('date_start') }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="date" name="date_end" class="form-control" value="{{ request('date_end') }}">
                        </div>
                        <div class="col-md-1 mb-3">
                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        </div>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Date & Time</th>
                                <th>User Yang Melakukan</th>
                                <th>Action</th>
                                <th>Role User Melakukan</th>
                                <th>Module</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ $log->user->username }}</td>
                                    <td>
                                        <span class="badge badge-{{ $log->action == 'created' ? 'success' : ($log->action == 'updated' ? 'info' : 'danger') }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->roleactive }}</td>
                                    <td>{{ ucfirst($log->module) }}</td>
                                    <td>{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No activity logs found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $logs->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection