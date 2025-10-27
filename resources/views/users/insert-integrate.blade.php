@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Integrate User from Siakad</h1>
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary shadow-sm">
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
                        <h6 class="m-0 font-weight-bold text-primary">Integrate User</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('users.insert-integrate.store') }}" autocomplete="off">
                            @csrf
                            <div class="form-group">
                                <label class="required" for="username">Username</label>
                                <input type="text" class="form-control" id="username_search" 
                                    placeholder="Ketik untuk mencari Username Siakad" required>
                                <input type="hidden" name="username" id="username_id" value="{{ old('username') }}">
                            </div>

                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                    value="{{ old('name') }}" readonly>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="{{ old('email') }}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="required">Roles</label>
                                @foreach ($roles as $role)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role{{ $role->id }}"
                                            name="roles[]" value="{{ $role->id }}">
                                        <label class="custom-control-label"
                                            for="role{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-group">
                                <label for="prodi">Program Studi</label>
                                <input type="text" class="form-control" id="prodi_text" readonly>
                                <input type="hidden" name="prodi" id="prodi_id">
                            </div>

                            <div class="form-group">
                                <label for="fakultas">Fakultas</label>
                                <input type="text" class="form-control" id="fakultas_text" readonly>
                                <input type="hidden" name="fakultas" id="fakultas_id">
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                        value="1" checked>
                                    <label class="custom-control-label" for="is_active">Akun Aktif</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-sync"></i> Integrate User
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .ui-menu-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #e3e6f0;
        }

        .ui-menu-item:hover {
            background: #4e73df;
            color: white;
        }
    </style>
@endpush

@push('js')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {
            // Pastikan document sudah ready
            console.log('Document ready');

            $('#username_search').autocomplete({
                source: function(request, response) {
                    console.log('Search term:', request.term);
                    $.ajax({
                        url: '{{ route('username.search') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            console.log('Response data:', data);
                            response($.map(data, function(item) {
                                return {
                                    label: item.text,
                                    value: item.text,
                                    username: item.id,
                                    user_data: item.user_data
                                };
                            }));
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    console.log('Selected:', ui.item);
                    // Isi username_id dengan username asli (tanpa nama)
                    $('#username_id').val(ui.item.username);
                    
                    // Namun tetap tampilkan username - nama di field pencarian
                    $('#username_search').val(ui.item.username);
                    
                    // Isi field lainnya secara otomatis
                    if (ui.item.user_data) {
                        $('#name').val(ui.item.user_data.name);
                        $('#email').val(ui.item.user_data.email);
                        $('#prodi_text').val(ui.item.user_data.prodi);
                        $('#prodi_id').val(ui.item.user_data.prodi);
                        $('#fakultas_text').val(ui.item.user_data.fakultas);
                        $('#fakultas_id').val(ui.item.user_data.fakultas);
                    }
                    // Prevent default behavior
                    return false;
                }
            });

            // Clear values when input is cleared
            $('#username_search').on('input', function() {
                if (!$(this).val()) {
                    $('#username_id').val('');
                    $('#name').val('');
                    $('#email').val('');
                    $('#prodi_text').val('');
                    $('#prodi_id').val('');
                    $('#fakultas_text').val('');
                    $('#fakultas_id').val('');
                }
            });
        });
    </script>
@endpush