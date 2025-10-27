@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Daftar Jadwal AMI</h1>
        <x-create-button route="jadwal-ami" title="Jadwal AMI" />
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Jadwal AMI</h6>
        </div>
        <div class="card-body">
            <form id="filter-form">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari prodi, fakultas, auditor..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <select name="periode" class="form-control">
                                <option value="">Semua Periode</option>
                                @foreach ($periodes as $periode)
                                    <option value="{{ $periode }}" {{ request('periode') == $periode ? 'selected' : '' }}>{{ $periode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <select name="standar_akreditasi" class="form-control">
                                <option value="">Semua Standar</option>
                                @foreach ($standarAkreditasi as $standar)
                                    <option value="{{ $standar }}" {{ request('standar_akreditasi') == $standar ? 'selected' : '' }}>{{ $standar }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="{{ route('jadwal-ami.index') }}" class="btn btn-secondary"><i class="fas fa-sync"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div id="jadwal-ami-table-container">
                @include('jadwal-ami._table', ['jadwalAmi' => $jadwalAmi])
            </div>
        </div>
    </div>

    {{-- Modal untuk Upload Statistics --}}
    <div class="modal fade" id="uploadStatsModal" tabindex="-1" role="dialog" aria-labelledby="uploadStatsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" id="uploadStatsModalLabel">
                        <i class="fas fa-chart-bar"></i> Statistik Upload
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="uploadStatsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Debounce function
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Function to fetch data
    const fetchData = (url) => {
        $.ajax({
            url: url,
            success: function(data) {
                $('#jadwal-ami-table-container').html(data);
            },
            error: function(xhr) {
                console.error('Error fetching data:', xhr.responseText);
                alert('Gagal memuat data. Silakan coba lagi.');
            }
        });
    };

    // Handle filter form submission
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        let url = new URL('{{ route("jadwal-ami.index") }}');
        url.search = new URLSearchParams($(this).serialize()).toString();
        fetchData(url.toString());
    });

    // Handle search input with debounce
    $('input[name="search"]').on('keyup', debounce(function() {
        $('#filter-form').submit();
    }, 500)); // 500ms delay

    // Handle filter dropdown change
    $('select[name="periode"], select[name="standar_akreditasi"]').on('change', function() {
        $('#filter-form').submit();
    });

    // Handle pagination clicks
    $(document).on('click', '#jadwal-ami-table-container .pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        fetchData(url);
    });

    // Function untuk toggle upload status
    window.toggleUpload = function(jadwalId, enable) {
        const action = enable ? 'mengaktifkan' : 'menonaktifkan';
        
        Swal.fire({
            title: `Konfirmasi`,
            text: `Apakah Anda yakin ingin ${action} upload untuk jadwal ini?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Ya, ${action.charAt(0).toUpperCase() + action.slice(1)}!`,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/jadwal-ami/${jadwalId}/toggle-upload`,
                    type: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}',
                        upload_enabled: enable
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload data instead of full page reload
                                $('#filter-form').submit();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan: ' + (xhr.responseJSON?.message || 'Unknown error')
                        });
                    }
                });
            }
        });
    };

    // Function untuk show upload statistics
    window.showUploadStats = function(jadwalId) {
        $('#uploadStatsModal').modal('show');
        $('#uploadStatsContent').html(`
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Loading...</p>
            </div>
        `);
        
        $.ajax({
            url: `/jadwal-ami/${jadwalId}/upload-stats`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary h-100">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Jadwal</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr><td><strong>Program Studi</strong></td><td>${data.jadwal_info.prodi}</td></tr>
                                            <tr><td><strong>Periode</strong></td><td>${data.jadwal_info.periode}</td></tr>
                                            <tr><td><strong>Status Upload</strong></td><td><span class="badge ${data.jadwal_info.upload_badge}">${data.jadwal_info.upload_status}</span></td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success h-100">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Statistik Upload</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr><td><strong>Total File</strong></td><td><span class="badge badge-info">${data.statistics.total_files}</span></td></tr>
                                            <tr><td><strong>Total Auditor</strong></td><td><span class="badge badge-secondary">${data.statistics.total_auditors}</span></td></tr>
                                            <tr><td><strong>Auditor Upload</strong></td><td><span class="badge badge-warning">${data.statistics.auditors_uploaded}</span></td></tr>
                                            <tr><td><strong>Total Size</strong></td><td><span class="badge badge-primary">${formatFileSize(data.statistics.total_file_size)}</span></td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    if (data.upload_schedule.upload_mulai) {
                        html += `
                            <div class="card border-info mt-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-calendar"></i> Jadwal Upload</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Mulai:</strong><br>
                                            <span class="text-primary">${formatDateTime(data.upload_schedule.upload_mulai)}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Selesai:</strong><br>
                                            <span class="text-primary">${formatDateTime(data.upload_schedule.upload_selesai)}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Status:</strong><br>
                                            <span class="badge ${data.upload_schedule.is_active ? 'badge-success' : 'badge-secondary'}">
                                                ${data.upload_schedule.is_active ? 'Aktif' : 'Tidak Aktif'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    if (data.recent_uploads.length > 0) {
                        html += `
                            <div class="card border-warning mt-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-upload"></i> Upload Terbaru</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                        `;
                        data.recent_uploads.forEach(upload => {
                            html += `
                                <div class="list-group-item p-2">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${upload.original_name}</h6>
                                        <small class="text-muted">${upload.file_size_formatted}</small>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> ${upload.auditor_name} - 
                                        <i class="fas fa-clock"></i> ${upload.uploaded_at}
                                    </small>
                                </div>
                            `;
                        });
                        html += '</div></div></div>';
                    }
                    
                    if (data.comments.length > 0) {
                        html += `
                            <div class="card border-secondary mt-3">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-comments"></i> Komentar Terbaru</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                        `;
                        data.comments.forEach(comment => {
                            html += `
                                <div class="list-group-item p-2">
                                    <div class="d-flex w-100 justify-content-between">
                                        <p class="mb-1">${comment.komentar}</p>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-user-tie"></i> ${comment.admin_name} - 
                                        <i class="fas fa-clock"></i> ${comment.created_at}
                                    </small>
                                </div>
                            `;
                        });
                        html += '</div></div></div>';
                    }
                    
                    $('#uploadStatsContent').html(html);
                }
            },
            error: function(xhr) {
                $('#uploadStatsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Error!</strong> Gagal memuat statistik: ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
            }
        });
    };

    // Delete confirmation - diperbaiki selector
    $(document).on('click', '.delete-confirm', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        Swal.fire({
            title: 'Apakah anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Helper functions
    window.formatFileSize = function(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    };

    window.formatDateTime = function(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Success/Error notifications
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
            timer: 5000,
            showConfirmButton: true
        });
    @endif
});
</script>
@endpush