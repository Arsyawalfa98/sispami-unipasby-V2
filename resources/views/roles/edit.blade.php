@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit Role</h1>
            <a href="{{ route('roles.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('roles.update', $role->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Role Name -->
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Role Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Permissions -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Permissions</label>
                                <button type="button" class="btn btn-sm btn-secondary" id="togglePermissions">
                                    <i class="fas fa-check-square"></i> Select All Permissions
                                </button>
                            </div>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($permissions as $permission)
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox" type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->id }}"
                                                   id="permission{{ $permission->id }}"
                                                   {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="permission{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Menu Access -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Menu Access</label>
                                <button type="button" class="btn btn-sm btn-secondary" id="toggleMenus">
                                    <i class="fas fa-check-square"></i> Select All Menus
                                </button>
                            </div>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($menus as $menu)
                                        <div class="form-check">
                                            <input class="form-check-input menu-checkbox" type="checkbox" 
                                                   name="menus[]" 
                                                   value="{{ $menu->id }}"
                                                   id="menu{{ $menu->id }}"
                                                   {{ $role->menus->contains($menu->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="menu{{ $menu->id }}">
                                                {{ $menu->name }}
                                            </label>
                                        </div>
                                        @if($menu->children->count() > 0)
                                            <div class="ml-4">
                                                @foreach($menu->children as $child)
                                                    <div class="form-check">
                                                        <input class="form-check-input menu-checkbox" type="checkbox" 
                                                               name="menus[]" 
                                                               value="{{ $child->id }}"
                                                               id="menu{{ $child->id }}"
                                                               {{ $role->menus->contains($child->id) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="menu{{ $child->id }}">
                                                            {{ $child->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Role</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // For Permissions
    let permissionsSelected = false;
    $("#togglePermissions").click(function() {
        permissionsSelected = !permissionsSelected;
        $(".permission-checkbox").prop('checked', permissionsSelected);
        $(this).html('<i class="fas fa-check-square"></i> ' + 
            (permissionsSelected ? 'Deselect All' : 'Select All') + ' Permissions');
    });

    // For Menus
    let menusSelected = false;
    $("#toggleMenus").click(function() {
        menusSelected = !menusSelected;
        $(".menu-checkbox").prop('checked', menusSelected);
        $(this).html('<i class="fas fa-check-square"></i> ' + 
            (menusSelected ? 'Deselect All' : 'Select All') + ' Menus');
    });
});
</script>
@endpush