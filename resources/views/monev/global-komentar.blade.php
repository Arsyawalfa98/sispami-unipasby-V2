@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            @if($statusTemuan === 'TERCAPAI')
                <i class="fas fa-check-circle text-success"></i> Komentar Global - TERCAPAI
            @else
                <i class="fas fa-exclamation-triangle text-warning"></i> Komentar Global - KTS
            @endif
            <br>
            <small class="text-muted">{{ $selectedProdi }}</small>
        </h1>
        @php
            $backRoute = $statusTemuan === 'TERCAPAI' ? 'monev-tercapai.showGroup' : 'monev-kts.showGroup';
        @endphp
        <a href="{{ route($backRoute, ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId, 'prodi' => $selectedProdi]) }}" 
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Card untuk menambah komentar global --}}
    @if(isset($userPermissions) && $userPermissions['can_comment'])
        <div class="card shadow mb-4">
            <div class="card-header {{ $statusTemuan === 'TERCAPAI' ? 'bg-success' : 'bg-warning' }}">
                <h6 class="m-0 font-weight-bold {{ $statusTemuan === 'TERCAPAI' ? 'text-white' : 'text-dark' }}">
                    <i class="fas fa-plus-circle"></i> Tambah Komentar Global
                </h6>
            </div>
            <div class="card-body">
                <form id="formKomentarGlobal">
                    @csrf
                    <input type="hidden" name="prodi" value="{{ $selectedProdi }}">
                    <input type="hidden" name="status_temuan" value="{{ $statusTemuan }}">
                    
                    <div class="form-group">
                        <label for="komentar_global">Komentar Global:</label>
                        <textarea name="komentar_global" 
                                  id="komentar_global" 
                                  class="form-control" 
                                  rows="5" 
                                  placeholder="Tulis komentar atau evaluasi global untuk seluruh program studi {{ $statusTemuan === 'TERCAPAI' ? 'yang tercapai' : 'dengan temuan KTS' }}..."
                                  maxlength="2000"
                                  required></textarea>
                        <small class="form-text text-muted">Maksimal 2000 karakter. <span id="charCount">0/2000</span></small>
                    </div>
                    
                    <button type="submit" class="btn {{ $statusTemuan === 'TERCAPAI' ? 'btn-success' : 'btn-warning' }}">
                        <i class="fas fa-paper-plane"></i> Kirim Komentar Global
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Card untuk menampilkan daftar komentar global --}}
    <div class="card shadow mb-4">
        <div class="card-header {{ $statusTemuan === 'TERCAPAI' ? 'bg-success' : 'bg-warning' }}">
            <h6 class="m-0 font-weight-bold {{ $statusTemuan === 'TERCAPAI' ? 'text-white' : 'text-dark' }}">
                <i class="fas fa-comments"></i> Daftar Komentar Global ({{ $globalKomentar->count() }})
            </h6>
        </div>
        <div class="card-body">
            @if($globalKomentar->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Belum ada komentar global. Jadilah yang pertama memberikan komentar!
                </div>
            @else
                <div id="komentarList">
                    @foreach($globalKomentar as $komentar)
                        <div class="card mb-3" id="komentar-{{ $komentar->id }}">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-circle text-primary"></i>
                                        <strong>{{ $komentar->admin->name }}</strong>
                                        <span class="badge {{ $statusTemuan === 'TERCAPAI' ? 'badge-success' : 'badge-warning' }} ml-2">
                                            {{ $statusTemuan }}
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> {{ $komentar->created_at->format('d M Y H:i') }}
                                        @if($komentar->created_at != $komentar->updated_at)
                                            <span class="ml-2">(Diubah: {{ $komentar->updated_at->format('d M Y H:i') }})</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text" style="white-space: pre-wrap;">{{ $komentar->komentar_global }}</p>
                            </div>
                            @if(isset($userPermissions))
                                <div class="card-footer bg-white">
                                    {{-- Button Edit: Pemilik komentar ATAU Super Admin --}}
                                    @if($userPermissions['can_edit_delete'] || $komentar->admin_id == Auth::id())
                                        <button class="btn btn-sm btn-primary" onclick="editKomentar({{ $komentar->id }}, '{{ addslashes($komentar->komentar_global) }}')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    @endif
                                    
                                    {{-- Button Delete: HANYA Super Admin --}}
                                    @if($userPermissions['can_edit_delete'])
                                        <button class="btn btn-sm btn-danger" onclick="deleteKomentar({{ $komentar->id }})">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('css')
<style>
    .card-text {
        text-align: justify;
        line-height: 1.6;
    }
    
    textarea#komentar_global {
        resize: vertical;
    }
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    // Character counter
    $('#komentar_global').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length + '/2000');
        
        if (length > 1900) {
            $('#charCount').addClass('text-danger');
        } else {
            $('#charCount').removeClass('text-danger');
        }
    });

    // Handle form submit komentar global
    $('#formKomentarGlobal').on('submit', function(e) {
        e.preventDefault();
        
        const komentarText = $('#komentar_global').val().trim();
        
        if (!komentarText) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian!',
                text: 'Komentar tidak boleh kosong.',
            });
            return;
        }
        
        @php
            $submitRoute = $statusTemuan === 'TERCAPAI' 
                ? route('monev-tercapai.storeGlobalKomentar', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId])
                : route('monev-kts.storeGlobalKomentar', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]);
        @endphp
        
        // Show loading
        Swal.fire({
            title: 'Menyimpan komentar...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '{{ $submitRoute }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                prodi: '{{ $selectedProdi }}',
                status_temuan: '{{ $statusTemuan }}',
                komentar_global: komentarText
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Komentar global berhasil ditambahkan.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                
                let errorMessage = 'Terjadi kesalahan saat menyimpan komentar global.';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('\n');
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});

// Function untuk edit komentar
function editKomentar(komentarId, currentText) {
    Swal.fire({
        title: 'Edit Komentar Global',
        input: 'textarea',
        inputLabel: 'Komentar Global',
        inputValue: currentText,
        inputAttributes: {
            maxlength: 2000,
            rows: 8,
            placeholder: 'Tulis komentar global...'
        },
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#3085d6',
        preConfirm: (text) => {
            if (!text || text.trim().length === 0) {
                Swal.showValidationMessage('Komentar tidak boleh kosong');
                return false;
            }
            if (text.length > 2000) {
                Swal.showValidationMessage('Komentar maksimal 2000 karakter');
                return false;
            }
            return text;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            updateKomentar(komentarId, result.value);
        }
    });
}

// Function untuk update komentar
function updateKomentar(komentarId, komentarText) {
    @php
        $statusTemuan = $statusTemuan ?? 'KETIDAKSESUAIAN';
        $updateRoute = $statusTemuan === 'TERCAPAI' 
            ? 'monev-tercapai.updateGlobalKomentar'
            : 'monev-kts.updateGlobalKomentar';
    @endphp
    
    Swal.fire({
        title: 'Menyimpan...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '{{ route($updateRoute, ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId, 'komentarId' => ':id']) }}'.replace(':id', komentarId),
        type: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            komentar_global: komentarText
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Komentar berhasil diperbarui.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        },
        error: function(xhr) {
            console.error('Update Error:', xhr);
            
            let errorMessage = 'Terjadi kesalahan saat mengubah komentar.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMessage
            });
        }
    });
}

// Function untuk delete komentar
function deleteKomentar(komentarId) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: "Apakah Anda yakin ingin menghapus komentar ini?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            @php
                $deleteRoute = $statusTemuan === 'TERCAPAI' 
                    ? 'monev-tercapai.destroyGlobalKomentar'
                    : 'monev-kts.destroyGlobalKomentar';
            @endphp
            
            Swal.fire({
                title: 'Menghapus...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: '{{ route($deleteRoute, ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId, 'komentarId' => ':id']) }}'.replace(':id', komentarId),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: 'Komentar berhasil dihapus.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Delete Error:', xhr);
                    
                    let errorMessage = 'Terjadi kesalahan saat menghapus komentar.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                }
            });
        }
    });
}

// SweetAlert notifications for flash messages
@if (session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        timer: 3000,
        showConfirmButton: false
    });
@endif

@if (session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
        timer: 3000,
        showConfirmButton: false
    });
@endif
</script>
@endpush