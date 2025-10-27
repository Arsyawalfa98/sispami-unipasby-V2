@props(['route', 'title'])

@if(auth()->user()->hasPermission('insert-integrate-users'))
    <a href="{{ route($route.'.insert-integrate') }}" class="btn btn-success btn-sm mb-3 ml-2">
        <i class="fas fa-sync"></i> Integrate {{ $title ?? ucfirst($route) }} from Siakad
    </a>
@endif