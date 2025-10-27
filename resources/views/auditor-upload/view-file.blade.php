{{-- resources/views/auditor-upload/view-file.blade.php --}}
@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Preview File</h1>
            <p class="mb-0 text-muted">{{ $upload->original_name }}</p>
        </div>
        <div>
            <a href="{{ route('auditor-upload.showGroup', [
                'standar_akreditasi' => $upload->jadwal->standar_akreditasi,
                'jenjang' => $upload->jadwal->periode,
                'periode' => $upload->jadwal->periode,
                'prodi' => $upload->jadwal->prodi
            ]) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('auditor-upload.download', $upload->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
    </div>

    {{-- File Info Card --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-info-circle"></i> Informasi File
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="{{ $upload->file_icon }} fa-3x mb-2"></i>
                        <h6 class="text-primary">{{ strtoupper(pathinfo($upload->original_name, PATHINFO_EXTENSION)) }}</h6>
                    </div>
                </div>
                <div class="col-md-9">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="150"><strong>Nama File:</strong></td>
                            <td>{{ $upload->original_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Ukuran File:</strong></td>
                            <td>{{ $upload->file_size_human }}</td>
                        </tr>
                        <tr>
                            <td><strong>Diupload oleh:</strong></td>
                            <td>{{ $upload->auditor->name ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Upload:</strong></td>
                            <td>{{ $upload->uploaded_at->format('d F Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Program Studi:</strong></td>
                            <td>{{ $upload->jadwal->prodi }}</td>
                        </tr>
                        <tr>
                            <td><strong>Fakultas:</strong></td>
                            <td>{{ $upload->jadwal->fakultas }}</td>
                        </tr>
                        @if($upload->keterangan)
                            <tr>
                                <td><strong>Keterangan:</strong></td>
                                <td>{{ $upload->keterangan }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF Viewer Card --}}
    @if(str_contains($upload->file_type, 'pdf'))
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-file-pdf"></i> Preview PDF
                </h6>
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-light btn-sm" onclick="zoomOut()">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="resetZoom()">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="zoomIn()">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="toggleFullscreen()">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="pdf-container" class="position-relative" style="min-height: 600px;">
                    <iframe id="pdf-viewer" 
                        src="{{ $fileUrl }}#toolbar=1&navpanes=1&scrollbar=1" 
                        width="100%" 
                        height="600px" 
                        style="border: none;"
                        onload="handleIframeLoad()"
                        onerror="handleIframeError()">
                        <p>Browser Anda tidak mendukung preview PDF. 
                           <a href="{{ route('auditor-upload.download', $upload->id) }}" class="btn btn-primary">
                               Download File
                           </a>
                        </p>
                    </iframe>
                    
                    {{-- Loading indicator --}}
                    <div id="loading-indicator" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Memuat PDF...</p>
                        </div>
                    </div>
                    
                    {{-- Error indicator --}}
                    <div id="error-indicator" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-light" style="display: none !important;">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>Gagal memuat preview PDF</h5>
                            <p class="text-muted">File mungkin rusak atau format tidak didukung.</p>
                            <a href="{{ route('auditor-upload.download', $upload->id) }}" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Non-PDF files --}}
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <i class="{{ $upload->file_icon }} fa-5x mb-4"></i>
                <h4>Preview Tidak Tersedia</h4>
                <p class="text-muted mb-4">
                    Preview hanya tersedia untuk file PDF. File ini berformat 
                    <strong>{{ strtoupper(pathinfo($upload->original_name, PATHINFO_EXTENSION)) }}</strong>.
                </p>
                <a href="{{ route('auditor-upload.download', $upload->id) }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-download"></i> Download File
                </a>
            </div>
        </div>
    @endif

    {{-- Navigation Card --}}
    <div class="card shadow">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">
                        <i class="fas fa-folder"></i> Lokasi File
                    </h6>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-light">
                            <li class="breadcrumb-item">
                                <a href="{{ route('auditor-upload.index') }}">Upload Auditor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('auditor-upload.showGroup', [
                                    'standar_akreditasi' => $upload->jadwal->standar_akreditasi,
                                    'jenjang' => $upload->jadwal->periode,
                                    'periode' => $upload->jadwal->periode,
                                    'prodi' => $upload->jadwal->prodi
                                ]) }}">
                                    {{ $upload->jadwal->prodi }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ Str::limit($upload->original_name, 30) }}
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <h6 class="text-primary">
                        <i class="fas fa-tools"></i> Aksi File
                    </h6>
                    <div class="btn-group" role="group">
                        <a href="{{ route('auditor-upload.download', $upload->id) }}" 
                           class="btn btn-success btn-sm" title="Download File">
                            <i class="fas fa-download"></i> Download
                        </a>
                        @if(Auth::user()->id == $upload->auditor_id && $upload->jadwal->isUploadActive())
                            <button class="btn btn-danger btn-sm" 
                                onclick="deleteFile({{ $upload->id }})" 
                                title="Hapus File">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    #pdf-container {
        background-color: #f8f9fa;
    }
    
    #pdf-viewer {
        transition: all 0.3s ease;
    }
    
    .btn-group .btn {
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-top-left-radius: 0.25rem;
        border-bottom-left-radius: 0.25rem;
    }
    
    .btn-group .btn:last-child {
        border-top-right-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }
    
    .table-borderless td {
        border: none;
        padding: 0.25rem 0.75rem;
    }
    
    .breadcrumb {
        padding: 0.5rem 1rem;
        margin-bottom: 0;
    }
    
    /* Fullscreen styles */
    .fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 9999;
        background: white;
    }
    
    .fullscreen iframe {
        width: 100% !important;
        height: 100% !important;
    }
</style>
@endpush

@push('js')
<script>
let currentZoom = 100;
let isFullscreen = false;

$(document).ready(function() {
    // Initialize tooltips
    $('[title]').tooltip();
    
    // Handle iframe load timeout
    setTimeout(function() {
        if ($('#loading-indicator').is(':visible')) {
            handleIframeError();
        }
    }, 10000); // 10 seconds timeout
});

function handleIframeLoad() {
    $('#loading-indicator').hide();
}

function handleIframeError() {
    $('#loading-indicator').hide();
    $('#error-indicator').show();
}

function zoomIn() {
    currentZoom += 25;
    if (currentZoom > 200) currentZoom = 200;
    applyZoom();
}

function zoomOut() {
    currentZoom -= 25;
    if (currentZoom < 50) currentZoom = 50;
    applyZoom();
}

function resetZoom() {
    currentZoom = 100;
    applyZoom();
}

function applyZoom() {
    const iframe = document.getElementById('pdf-viewer');
    iframe.style.transform = `scale(${currentZoom / 100})`;
    iframe.style.transformOrigin = 'top left';
    
    if (currentZoom !== 100) {
        const container = document.getElementById('pdf-container');
        container.style.overflow = 'auto';
        iframe.style.width = `${100 * (100 / currentZoom)}%`;
        iframe.style.height = `${600 * (currentZoom / 100)}px`;
    } else {
        iframe.style.width = '100%';
        iframe.style.height = '600px';
    }
}

function toggleFullscreen() {
    const container = document.getElementById('pdf-container');
    const iframe = document.getElementById('pdf-viewer');
    
    if (!isFullscreen) {
        // Enter fullscreen
        container.classList.add('fullscreen');
        iframe.style.height = '100vh';
        isFullscreen = true;
        
        // Change button icon
        document.querySelector('[onclick="toggleFullscreen()"] i').className = 'fas fa-compress';
        
        // Add escape key listener
        document.addEventListener('keydown', handleEscapeKey);
    } else {
        // Exit fullscreen
        exitFullscreen();
    }
}

function exitFullscreen() {
    const container = document.getElementById('pdf-container');
    const iframe = document.getElementById('pdf-viewer');
    
    container.classList.remove('fullscreen');
    iframe.style.height = '600px';
    isFullscreen = false;
    
    // Change button icon back
    document.querySelector('[onclick="toggleFullscreen()"] i').className = 'fas fa-expand';
    
    // Remove escape key listener
    document.removeEventListener('keydown', handleEscapeKey);
    
    // Reapply zoom if needed
    if (currentZoom !== 100) {
        applyZoom();
    }
}

function handleEscapeKey(event) {
    if (event.key === 'Escape' && isFullscreen) {
        exitFullscreen();
    }
}

function deleteFile(fileId) {
    Swal.fire({
        title: 'Hapus File?',
        text: "File yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/auditor-upload/${fileId}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
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
                            // Redirect back to group page
                            window.location.href = '{{ route("auditor-upload.showGroup", [
                                "standar_akreditasi" => $upload->jadwal->standar_akreditasi,
                                "jenjang" => $upload->jadwal->periode,
                                "periode" => $upload->jadwal->periode,
                                "prodi" => $upload->jadwal->prodi
                            ]) }}';
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus file.'
                    });
                }
            });
        }
    });
}

// Handle browser back button for fullscreen
window.addEventListener('popstate', function(event) {
    if (isFullscreen) {
        exitFullscreen();
    }
});

// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (document.hidden && isFullscreen) {
        exitFullscreen();
    }
});
</script>
@endpush