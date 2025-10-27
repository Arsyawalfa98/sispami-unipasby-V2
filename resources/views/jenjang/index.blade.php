@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Jenjang</h1>
        <x-create-button route="jenjang" title="Jenjang" />
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('jenjang.index') }}" class="mb-0">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari Nama Jenjang..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                        <a href="{{ route('jenjang.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Jenjang</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jenjang as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->nama }}</td>
                                <td>
                                    <x-action-buttons 
                                        route="jenjang" 
                                        :id="$item->id" 
                                        permission="jenjang"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $jenjang->links() }}
        </div>
    </div>
@endsection