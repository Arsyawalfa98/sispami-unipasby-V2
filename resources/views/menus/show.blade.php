@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Menu Detail</h1>
            <a href="{{ route('menus.index') }}" class="btn btn-sm btn-primary shadow-sm">
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
                            <p>{{ $menu->name }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>URL:</strong>
                            <p>{{ $menu->url ?: 'No URL (Parent Menu)' }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Icon:</strong>
                            <p><i class="{{ $menu->icon }}"></i> {{ $menu->icon }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Order:</strong>
                            <p>{{ $menu->order }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <p>
                                @if($menu->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>Menu Structure</h5>
                        <hr>
                        <div class="mb-3">
                            <strong>Type:</strong>
                            <p>
                                @if($menu->parent_id)
                                    <span class="badge badge-info">Child Menu</span>
                                    <br>
                                    <strong>Parent Menu:</strong> {{ $menu->parent->name ?? 'N/A' }}
                                @else
                                    <span class="badge badge-primary">Parent Menu</span>
                                @endif
                            </p>
                        </div>

                        @if(!$menu->parent_id)
                            <div class="mb-3">
                                <strong>Child Menus:</strong>
                                @if($menu->children->count() > 0)
                                    <ul class="list-group mt-2">
                                        @foreach($menu->children as $child)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                {{ $child->name }}
                                                @if($child->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p>No child menus</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if(auth()->user()->hasPermission('edit-menus'))
                    <div class="mt-3">
                        <a href="{{ route('menus.edit', $menu->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Menu
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
        font-size: 90%;
    }
</style>
@endpush