@props(['route', 'id'])

<div class="btn-group" role="group">
    @if(auth()->user()->hasPermission('view-' . $route))
        <a href="{{ route($route.'.show', $id) }}" class="btn btn-info btn-sm">
            <i class="fas fa-eye"></i>
        </a>
    @endif

    @if(auth()->user()->hasPermission('edit-' . $route))
        <a href="{{ route($route.'.edit', $id) }}" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i>
        </a>
    @endif

    @if(auth()->user()->hasPermission('delete-' . $route))
        <form action="{{ route($route.'.destroy', $id) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            {{-- <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin Menghapus Item ini?')"> --}}
            <button type="submit" class="btn btn-danger btn-sm delete-confirm">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    @endif
</div>