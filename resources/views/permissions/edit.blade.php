@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit Permission</h1>
            <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-left-danger" role="alert">
                <ul class="pl-4 my-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Permission Details</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('permissions.update', $permission->id) }}" autocomplete="off">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label class="required" for="name">Permission Name</label>
                                <input type="text" 
                                       class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Enter permission name"
                                       value="{{ old('name', $permission->name) }}"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">
                                    Example: create-users, edit-roles, view-permissions
                                </small>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Permission
                                </button>
                                <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <dl>
                                    <dt>Created At:</dt>
                                    <dd>{{ $permission->created_at->format('d/m/Y H:i') }}</dd>
                                    <dt>Last Updated:</dt>
                                    <dd>{{ $permission->updated_at->format('d/m/Y H:i') }}</dd>
                                    <dt>Used In Roles:</dt>
                                    <dd>
                                        @forelse($permission->roles as $role)
                                            <span class="badge badge-info">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-muted">No roles using this permission</span>
                                        @endforelse
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .required:after {
        content: '*';
        color: red;
        margin-left: 3px;
    }
</style>
@endpush