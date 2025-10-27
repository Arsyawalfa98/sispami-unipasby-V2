@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
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
                        <h6 class="m-0 font-weight-bold text-primary">User Details</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('users.update', $user->id) }}" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label class="required" for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name', $user->name) }}" required>
                            </div>

                            <div class="form-group">
                                <label class="required" for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="{{ old('username', $user->username) }}" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="required" for="email">Email</label>
                                <input type="text" class="form-control" id="email" name="email"
                                    value="{{ old('email', $user->email) }}" required>
                            </div>

                            <div class="form-group">
                                <label class="required">Roles</label>
                                @foreach ($roles as $role)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role{{ $role->id }}"
                                            name="roles[]" value="{{ $role->id }}"
                                            {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="role{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-group" hidden>
                                <label for="jabatan">Jabatan</label>
                                <input type="text" class="form-control @error('jabatan') is-invalid @enderror"
                                    id="jabatan" name="jabatan" value="{{ old('jabatan', $user->jabatan) }}">
                                @error('jabatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="prodi">Program Studi</label>
                                <input type="text" class="form-control" id="prodi_search"
                                    placeholder="Ketik untuk mencari Program Studi" readonly>
                                <input type="hidden" name="prodi" id="prodi_id" value="{{ $user->prodi ?? '' }}">
                            </div>

                            <div class="form-group">
                                <label for="fakultas">Fakultas</label>
                                <input type="text" class="form-control" id="fakultas_text" readonly>
                                <input type="hidden" name="fakultas" id="fakultas_id">
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                        value="1" {{ $user->is_active ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">Akun Aktif</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Password <small class="text-muted">(Leave blank to keep current
                                        password)</small></label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Multi-Prodi Info & Sync Card -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-university"></i> Multi-Prodi Management
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Prodis Tersync:</strong>
                            <div id="prodi-list" class="mt-2">
                                @if($user->prodis->isNotEmpty())
                                    @foreach($user->prodis as $prodi)
                                        <span class="badge badge-{{ $prodi->is_default ? 'success' : 'secondary' }}" title="{{ $prodi->nama_fakultas }}">
                                            {{ $prodi->kode_prodi }} - {{ $prodi->nama_prodi }}
                                            @if($prodi->is_default)
                                                <i class="fas fa-star fa-xs ml-1" title="Default"></i>
                                            @endif
                                        </span>
                                    @endforeach
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-info-circle"></i> Total: {{ $user->prodis->count() }} prodi
                                    </small>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <small>Belum ada prodi tersync dari sistem mitra.</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(auth()->user()->hasRole('Super Admin'))
                            <hr>
                            <div class="mb-3">
                                <button type="button" class="btn btn-info btn-block" id="btn-sync-prodi">
                                    <i class="fas fa-sync-alt"></i> Sync Prodi dari Mitra
                                </button>
                                <div id="sync-status" class="mt-2"></div>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i>
                                <strong>Info:</strong> Sync akan mengambil data dari:
                                <ul class="mb-0 mt-1" style="font-size: 0.85rem;">
                                    <li>MITRA</li>
                                </ul>
                            </small>
                        @else
                            <div class="alert alert-info mb-0">
                                <small><i class="fas fa-info-circle"></i> Prodi otomatis sync saat user login.</small>
                            </div>
                        @endif
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
            // Set nilai awal untuk prodi dan fakultas
            @if($user->prodi)
                $('#prodi_search').val("{{ $user->prodi }}");
                $('#prodi_id').val("{{ explode('-', $user->prodi)[0] }}");
            @endif

            @if($user->fakultas)
                $('#fakultas_text').val("{{ $user->fakultas }}");
                $('#fakultas_id').val("{{ explode('-', $user->fakultas)[0] }}");
            @endif

            $('#prodi_search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '{{ route('prodi.search') }}',
                        dataType: 'json',
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.text,
                                    value: item.text,
                                    id: item.id,
                                    fakultas: item.fakultas
                                };
                            }));
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    $('#prodi_id').val(ui.item.id);
                    if (ui.item.fakultas) {
                        $('#fakultas_text').val(ui.item.fakultas.text);
                        $('#fakultas_id').val(ui.item.fakultas.id);
                    }
                }
            });

            // Clear values when input is cleared
            $('#prodi_search').on('input', function() {
                if (!$(this).val()) {
                    $('#prodi_id').val('');
                    $('#fakultas_text').val('');
                    $('#fakultas_id').val('');
                }
            });

            // Sync Prodi Button Handler
            $('#btn-sync-prodi').on('click', function() {
                const btn = $(this);
                const statusDiv = $('#sync-status');
                const prodiList = $('#prodi-list');

                // Disable button
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm" role="status"></span> Syncing...');

                // Show loading status
                statusDiv.html('<div class="alert alert-info mb-0"><i class="fas fa-spinner fa-spin"></i> Sedang sync data dari sistem mitra...</div>');

                $.ajax({
                    url: '{{ route("users.sync-prodi", $user->id) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update prodi list
                            let prodisHtml = '';
                            let isFirst = true;

                            response.data.prodis.forEach(function(prodi) {
                                const badgeClass = isFirst ? 'success' : 'secondary';
                                const star = isFirst ? '<i class="fas fa-star fa-xs ml-1" title="Default"></i>' : '';
                                prodisHtml += `<span class="badge badge-${badgeClass}" title="${prodi.nama_fakultas}">
                                                ${prodi.kode_prodi} - ${prodi.nama_prodi} ${star}
                                              </span>`;
                                isFirst = false;
                            });

                            prodisHtml += `<small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> Total: ${response.data.total_prodis} prodi</small>`;
                            prodiList.html(prodisHtml);

                            // Show success message
                            let newProdisMsg = '';
                            if (response.data.new_prodis.length > 0) {
                                newProdisMsg = '<br><small>Prodi baru: ' + response.data.new_prodis.join(', ') + '</small>';
                            }

                            statusDiv.html(`<div class="alert alert-success mb-0 alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i> ${response.message}${newProdisMsg}
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>`);

                            // Auto hide after 5 seconds
                            setTimeout(function() {
                                statusDiv.fadeOut();
                            }, 5000);
                        } else {
                            statusDiv.html(`<div class="alert alert-danger mb-0 alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> ${response.message}
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>`);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Terjadi kesalahan saat sync prodi';
                        statusDiv.html(`<div class="alert alert-danger mb-0 alert-dismissible fade show">
                            <i class="fas fa-times-circle"></i> ${message}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>`);
                    },
                    complete: function() {
                        // Re-enable button
                        btn.prop('disabled', false);
                        btn.html('<i class="fas fa-sync-alt"></i> Sync Prodi dari Mitra');
                    }
                });
            });
        });
    </script>
@endpush
