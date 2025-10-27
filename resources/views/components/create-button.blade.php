@props(['route', 'title', 'params' => [], 'suffix' => 'create'])

@if(auth()->user()->hasPermission('create-' . $route))
    <a href="{{ route($route.'.'.$suffix, $params) }}" class="btn btn-primary btn-sm mb-3">
        <i class="fas fa-plus"></i> Add New {{ $title ?? ucfirst($route) }}
    </a>
@endif