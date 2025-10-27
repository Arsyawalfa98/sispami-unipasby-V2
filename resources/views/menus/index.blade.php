@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Menu Management') }}</h1>
        <x-create-button route="menus" title="Menu" />
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
            <h6 class="m-0 font-weight-bold text-primary">Menus List</h6>
        </div>
        <div class="card-body">
            <!-- Search and Filter Form -->
            <form id="search-form" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <select name="type" class="form-control" onchange="this.form.submit()">
                            <option value="">All Menu Types</option>
                            @foreach($menuTypes as $value => $label)
                                <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by menu name..." 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="{{ route('menus.index') }}" class="btn btn-secondary">
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
                            <th>URL</th>
                            <th>Parent Menu</th>
                            <th>Order</th>
                            <th>Aktif Menu</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            <tr>
                                <td>
                                    <i class="{{ $menu->icon }}"></i> {{ $menu->name }}
                                </td>
                                <td>{{ $menu->url ?: '-' }}</td>
                                <td>
                                    @if($menu->parent)
                                        <span class="badge badge-info">{{ $menu->parent->name }}</span>
                                    @else
                                        <span class="badge badge-secondary">No Parent</span>
                                    @endif
                                </td>
                                <td>{{ $menu->order }}</td>
                                <td> 
                                    @if($menu->is_active)
                                        <span class="badge badge-primary">Aktif</span>
                                    @else
                                        <span class="badge badge-danger">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <x-action-buttons route="menus" :id="$menu->id" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No menus found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $menus->withQueryString()->links() }}
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
.fa, .fas {
    margin-right: 5px;
}
</style>
@endpush