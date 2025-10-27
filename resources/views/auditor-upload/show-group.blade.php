{{-- resources/views/auditor-upload/show-group.blade.php --}}
@extends('layouts.admin')

@section('main-content')
    @if(isset($showProdiSelection) && $showProdiSelection)
        {{-- Prodi Selection Mode --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Upload File Auditor</h1>
                <p class="mb-0 text-muted">
                    {{ $standarAkreditasi }} - {{ $periode }}
                </p>
            </div>
            <a href="{{ route('auditor-upload.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- Dropdown Selection untuk Prodi --}}
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-university"></i> Pilih Program Studi
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Terdapat <strong>{{ $prodiList->count() }}</strong> program studi untuk lembaga/jenjang ini.
                            Silakan pilih program studi untuk melihat data upload.
                        </div>

                        <form method="GET" action="{{ route('auditor-upload.showGroup', ['lembagaId' => $lembagaId, 'jenjangId' => $jenjangId]) }}">
                            <!-- Hidden inputs untuk preserve parameters -->
                            <input type="hidden" name="periode" value="{{ $periode }}">

                            <div class="form-group">
                                <label for="prodi_select" class="font-weight-bold">Program Studi:</label>
                                <select name="prodi" id="prodi_select" class="form-control form-control-lg" required>
                                    <option value="">-- Pilih Program Studi --</option>
                                    @foreach($prodiList as $prodi)
                                        <option value="{{ $prodi['prodi'] }}">
                                            {{ $prodi['prodi'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-arrow-right"></i> Tampilkan Data Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
    {{-- Normal Upload Display Mode --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Upload File Auditor</h1>
            <p class="mb-0 text-muted">
                {{ $jadwal->prodi }} - {{ $jadwal->fakultas }} - {{ $jadwal->standar_akreditasi }}
                ({{ substr($jadwal->periode, 0, 4) }})
            </p>
        </div>
        <a href="{{ route('auditor-upload.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Informasi Jadwal Upload Card --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-info-circle"></i> Informasi Jadwal Upload
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Status Upload --}}
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-primary">Status Upload</h6>
                        <span class="badge badge-lg {{ $jadwal->upload_status_badge }}">
                            {{ $jadwal->upload_status }}
                        </span>
                    </div>
                </div>

                {{-- Jadwal AMI --}}
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-primary">Jadwal AMI</h6>
                        <small>
                            <strong>Mulai:</strong>
                            {{ \Carbon\Carbon::parse($jadwal->tanggal_mulai)->format('d M Y H:i') }}<br>
                            <strong>Selesai:</strong>
                            {{ \Carbon\Carbon::parse($jadwal->tanggal_selesai)->format('d M Y H:i') }}
                        </small>
                    </div>
                </div>

                {{-- Periode Upload --}}
                <div class="col-md-3">
                    @if ($jadwal->upload_mulai && $jadwal->upload_selesai)
                        <div class="text-center">
                            <h6 class="text-primary">Periode Upload</h6>
                            <small>
                                <strong>Mulai:</strong>
                                {{ \Carbon\Carbon::parse($jadwal->upload_mulai)->format('d M Y H:i') }}<br>
                                <strong>Selesai:</strong>
                                {{ \Carbon\Carbon::parse($jadwal->upload_selesai)->format('d M Y H:i') }}
                            </small>
                        </div>
                    @else
                        <div class="text-center">
                            <h6 class="text-primary">Periode Upload</h6>
                            <small class="text-muted">Belum ditetapkan</small>
                        </div>
                    @endif
                </div>

                {{-- Tim Auditor --}}
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-primary">Tim Auditor</h6>
                        @if ($timAuditor['ketua'])
                            <span class="badge badge-warning mb-1">
                                <i class="fas fa-crown"></i> {{ $timAuditor['ketua']->name }}
                            </span><br>
                        @endif
                        @foreach ($timAuditor['anggota'] as $anggota)
                            <span class="badge badge-info mb-1">
                                <i class="fas fa-user"></i> {{ $anggota->name }}
                            </span>
                            @if (!$loop->last)
                                <br>
                            @endif
                        @endforeach
                        @if (!$timAuditor['ketua'] && $timAuditor['anggota']->count() == 0)
                            <small class="text-muted">Belum ditetapkan</small>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Keterangan --}}
            @if ($jadwal->upload_keterangan)
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i> <strong>Keterangan:</strong> {{ $jadwal->upload_keterangan }}
                </div>
            @endif
        </div>
    </div>

    {{-- Upload Form --}}
    @if ($userPermissions['can_upload'])
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-upload"></i> Upload File
                    @if ($userPermissions['is_super_admin'])
                        <small class="badge badge-warning ml-2">
                            <i class="fas fa-user-shield"></i> Super Admin Mode
                        </small>
                    @elseif($userPermissions['is_admin_lpm'])
                        <small class="badge badge-info ml-2">
                            <i class="fas fa-user-tie"></i> Admin LPM Mode
                        </small>
                    @elseif($userPermissions['is_assigned_auditor'])
                        <small class="badge badge-light ml-2">
                            <i class="fas fa-user"></i> Auditor Mode
                        </small>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                {{-- Mode Info Alert --}}
                @if ($userPermissions['is_super_admin'] || $userPermissions['is_admin_lpm'])
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i>
                        <strong>Mode Khusus:</strong>
                        @if ($userPermissions['is_super_admin'])
                            Anda dapat upload file kapan saja sebagai Super Admin.
                        @else
                            Anda dapat upload file dalam periode aktif sebagai Admin LPM.
                        @endif
                        File akan tersimpan dengan identitas {{ Auth::user()->name }}.
                    </div>
                @endif

                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="jadwal_ami_id" value="{{ $jadwal->id }}">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="file">Pilih File <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="file" id="file"
                                        accept=".pdf,.doc,.docx" required>
                                    <label class="custom-file-label" for="file">Pilih file untuk upload
                                        otomatis...</label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    File akan langsung diupload saat dipilih. Format: PDF, DOC, DOCX (Maks 10MB)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> File akan langsung diupload setelah dipilih.
                        Pastikan file yang dipilih sudah benar sebelum memilihnya.
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Files by Auditor --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-file-alt"></i> File Upload ({{ $uploadStats['total_files'] }} file)
            </h6>
            @if ($uploadStats['total_files'] > 0 && $userPermissions['can_download'])
                <a href="{{ route('auditor-upload.bulk-download', $jadwal->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-download"></i> Download Semua
                </a>
            @endif
        </div>
        <div class="card-body">
            @if ($uploadsByAuditor->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>Belum Ada File Upload</h5>
                    <p class="mb-0">
                        @if ($userPermissions['can_upload'])
                            Silakan upload file menggunakan form di atas.
                        @else
                            Belum ada file yang diupload oleh auditor.
                        @endif
                    </p>
                </div>
            @else
                @foreach ($uploadsByAuditor as $auditorId => $uploads)
                    @php
                        $auditor = $uploads->first()->auditor;
                    @endphp
                    <div class="card border-left-primary mb-3">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-user text-primary"></i>
                                    {{ $auditor->name }}
                                    <small class="text-muted">({{ $uploads->count() }} file)</small>
                                </h6>
                                <small class="text-muted">
                                    Upload terakhir:
                                    {{ $uploads->sortByDesc('uploaded_at')->first()->uploaded_at->format('d M Y H:i') }}
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama File</th>
                                            <th>Ukuran</th>
                                            <th>Tanggal Upload</th>
                                            <th>Keterangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($uploads->sortByDesc('uploaded_at') as $upload)
                                            <tr id="file-row-{{ $upload->id }}">
                                                <td>
                                                    <i class="{{ $upload->file_icon }}"></i>
                                                    {{ $upload->original_name }}
                                                </td>
                                                <td>{{ $upload->file_size_human }}</td>
                                                <td>{{ $upload->uploaded_at->format('d M Y H:i') }}</td>
                                                <td>
                                                    <div id="keterangan-display-{{ $upload->id }}">
                                                        @if ($upload->keterangan)
                                                            <span class="text-muted" title="{{ $upload->keterangan }}">
                                                                {{ Str::limit($upload->keterangan, 30) }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">Belum ada keterangan</span>
                                                        @endif

                                                        {{-- Tombol edit keterangan --}}
                                                        @if ($upload->auditor_id == Auth::user()->id || $userPermissions['can_comment'])
                                                            <button class="btn btn-sm btn-outline-secondary ml-1"
                                                                onclick="editKeterangan({{ $upload->id }}, '{{ addslashes($upload->keterangan ?? '') }}')"
                                                                title="Edit Keterangan">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        {{-- View Button (PDF only) --}}
                                                        @if ($upload->isViewable && $userPermissions['can_download'])
                                                            <a href="{{ $upload->view_url }}" class="btn btn-info btn-sm"
                                                                title="Lihat File" target="_blank">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endif

                                                        {{-- Download Button --}}
                                                        @if ($userPermissions['can_download'])
                                                            <a href="{{ $upload->download_url }}"
                                                                class="btn btn-success btn-sm" title="Download File">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        @endif

                                                        {{-- Delete Button --}}
                                                        @if (
                                                            ($userPermissions['is_assigned_auditor'] && $upload->auditor_id == Auth::user()->id && $jadwal->isUploadActive()) ||
                                                                $userPermissions['can_comment']
                                                        )
                                                            <button class="btn btn-danger btn-sm"
                                                                onclick="deleteFile({{ $upload->id }})"
                                                                title="Hapus File">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Comments Section (untuk Admin LPM) --}}
    @if ($userPermissions['can_comment'] || $jadwal->uploadComments->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-comments"></i> Komentar Admin ({{ $jadwal->uploadComments->count() }})
                </h6>
            </div>
            <div class="card-body">
                {{-- Add Comment Form --}}
                @if ($userPermissions['can_comment'])
                    <form id="commentForm" class="mb-4">
                        @csrf
                        <div class="form-group">
                            <label for="komentar">Tambah Komentar</label>
                            <textarea class="form-control" name="komentar" id="komentar" rows="3"
                                placeholder="Tulis komentar atau feedback untuk auditor..." maxlength="1000" required></textarea>
                            <small class="form-text text-muted">Maksimal 1000 karakter.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Komentar
                        </button>
                    </form>
                    <hr>
                @endif

                {{-- Comments List --}}
                <div id="comments-list">
                    @if ($jadwal->uploadComments->count() > 0)
                        @foreach ($jadwal->uploadComments->sortByDesc('created_at') as $comment)
                            <div class="card border-left-info mb-3" id="comment-{{ $comment->id }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <i class="fas fa-user-tie text-info"></i>
                                                {{ $comment->admin->name ?? 'Admin' }}
                                            </h6>
                                            <p class="mb-2" id="comment-text-{{ $comment->id }}">
                                                {{ $comment->komentar }}</p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i>
                                                {{ $comment->created_at->format('d M Y H:i') }}
                                                @if ($comment->created_at != $comment->updated_at)
                                                    <em>(diubah {{ $comment->updated_at->format('d M Y H:i') }})</em>
                                                @endif
                                            </small>
                                        </div>

                                        {{-- Edit/Delete buttons untuk comment owner atau Super Admin --}}
                                        @if (
                                            $userPermissions['can_comment'] &&
                                                ($comment->admin_id == Auth::user()->id || Auth::user()->hasActiveRole('Super Admin')))
                                            <div class="btn-group ml-2" role="group">
                                                <button class="btn btn-outline-primary btn-sm"
                                                    onclick="editComment({{ $comment->id }}, '{{ addslashes($comment->komentar) }}')"
                                                    title="Edit Komentar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm"
                                                    onclick="deleteComment({{ $comment->id }})" title="Hapus Komentar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info text-center" id="no-comments">
                            <i class="fas fa-comment-slash fa-2x mb-3"></i>
                            <h5>Belum Ada Komentar</h5>
                            <p class="mb-0">
                                @if ($userPermissions['can_comment'])
                                    Jadilah yang pertama memberikan komentar atau feedback.
                                @else
                                    Belum ada komentar dari admin.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    @endif {{-- End of showProdiSelection check --}}
@endsection

@push('css')
    <style>
        .badge-lg {
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
        }

        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .progress {
            height: 1.5rem;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .card-header.bg-info {
            background-color: #36b9cc !important;
        }

        .card-header.bg-success {
            background-color: #1cc88a !important;
        }

        .card-header.bg-dark {
            background-color: #5a5c69 !important;
        }

        .card-header.bg-secondary {
            background-color: #858796 !important;
        }

        .custom-file-label::after {
            content: "Browse";
        }
    </style>
@endpush

@push('js')
    @if(!isset($showProdiSelection) || !$showProdiSelection)
    {{-- Only load JavaScript if in normal display mode (not selection mode) --}}
    <script>
        $(document).ready(function() {
            // Custom file input label update
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            // Upload form handler - UPDATED untuk security flow
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(this);
                let submitBtn = $(this).find('button[type="submit"]');
                let originalText = submitBtn.html();

                // Validate file
                let fileInput = $('#file')[0];
                if (!fileInput.files[0]) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Silakan pilih file terlebih dahulu.'
                    });
                    return;
                }

                let file = fileInput.files[0];

                // Validate file type
                const allowedTypes = ['application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Format File Tidak Valid!',
                        text: 'File harus berformat PDF, DOC, atau DOCX.'
                    });
                    return;
                }

                // Validate file size (10MB)
                if (file.size > 10485760) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ukuran File Terlalu Besar!',
                        text: 'Ukuran file maksimal 10MB.'
                    });
                    return;
                }

                // Disable button and show loading
                submitBtn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Uploading...');

                $.ajax({
                    url: '{{ route('auditor-upload.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Upload Berhasil!',
                                text: response.message +
                                    ' Anda dapat menambahkan keterangan pada file di tabel di bawah.',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors || {};
                        let message = xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat upload file.';

                        if (Object.keys(errors).length > 0) {
                            let errorText = '';
                            Object.values(errors).forEach(errorArray => {
                                errorArray.forEach(error => {
                                    errorText += error + '\n';
                                });
                            });
                            message = errorText;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Gagal!',
                            text: message
                        });
                    },
                    complete: function() {
                        // Re-enable button and reset form
                        submitBtn.prop('disabled', false).html(originalText);
                        $('#uploadForm')[0].reset();
                        $('.custom-file-label').removeClass("selected").html("Pilih file...");
                    }
                });
            });

            // Comment form handler
            $('#commentForm').on('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(this);
                let submitBtn = $(this).find('button[type="submit"]');
                let originalText = submitBtn.html();

                // Disable button and show loading
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');

                $.ajax({
                    url: '{{ route('auditor-upload.comment.store', $jadwal->id) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat menyimpan komentar.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    },
                    complete: function() {
                        // Re-enable button and clear form
                        submitBtn.prop('disabled', false).html(originalText);
                        $('#komentar').val('');
                    }
                });
            });

            // Tooltip initialization
            $('[title]').tooltip();
        });

        // TAMBAHAN BARU: Function untuk edit keterangan file
        function editKeterangan(uploadId, currentKeterangan) {
            Swal.fire({
                title: 'Edit Keterangan File',
                input: 'textarea',
                inputLabel: 'Keterangan',
                inputValue: currentKeterangan,
                inputAttributes: {
                    maxlength: 500,
                    rows: 4,
                    placeholder: 'Tulis keterangan atau catatan untuk file ini...'
                },
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745',
                preConfirm: (text) => {
                    if (text && text.length > 500) {
                        Swal.showValidationMessage('Keterangan maksimal 500 karakter');
                        return false;
                    }
                    return text;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateKeterangan(uploadId, result.value || '');
                }
            });
        }

        // TAMBAHAN BARU: Function untuk update keterangan via AJAX
        function updateKeterangan(uploadId, keterangan) {
            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `{{ url('auditor-upload') }}/${uploadId}/update-keterangan`, // Perbaikan URL
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}',
                    keterangan: keterangan
                },
                success: function(response) {
                    if (response.success) {
                        // Update display keterangan di tabel
                        const displayElement = document.getElementById(`keterangan-display-${uploadId}`);
                        const span = displayElement.querySelector('span:first-child');

                        if (keterangan.trim()) {
                            span.textContent = keterangan.length > 30 ? keterangan.substring(0, 30) + '...' :
                                keterangan;
                            span.setAttribute('title', keterangan);
                            span.className = 'text-muted';
                        } else {
                            span.textContent = 'Belum ada keterangan';
                            span.removeAttribute('title');
                            span.className = 'text-muted';
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Keterangan berhasil diperbarui.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr); // Debug info
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat menyimpan keterangan.'
                    });
                }
            });
        }

        // Delete file function
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
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus file.'
                            });
                        }
                    });
                }
            });
        }

        // Edit comment function
        function editComment(commentId, currentText) {
            Swal.fire({
                title: 'Edit Komentar',
                input: 'textarea',
                inputValue: currentText,
                inputAttributes: {
                    maxlength: 1000,
                    rows: 4
                },
                showCancelButton: true,
                confirmButtonText: 'Update',
                cancelButtonText: 'Batal',
                preConfirm: (text) => {
                    if (!text.trim()) {
                        Swal.showValidationMessage('Komentar tidak boleh kosong');
                        return false;
                    }
                    return text;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/auditor-upload/{{ $jadwal->id }}/comment/${commentId}`,
                        type: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            keterangan: result.value
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
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat mengubah komentar.'
                            });
                        }
                    });
                }
            });
        }

        // Delete comment function
        function deleteComment(commentId) {
            Swal.fire({
                title: 'Hapus Komentar?',
                text: "Komentar yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/auditor-upload/{{ $jadwal->id }}/comment/${commentId}`,
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
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus komentar.'
                            });
                        }
                    });
                }
            });

        }

        $(document).ready(function() {
            // Auto-upload saat file dipilih
            $('#file').on('change', function() {
                if (this.files && this.files[0]) {
                    // Update label dengan nama file
                    let fileName = $(this).val().split('\\').pop();
                    $(this).next('.custom-file-label').addClass("selected").html(fileName);

                    // Auto-upload
                    uploadFile();
                }
            });

            // Function untuk upload file
            function uploadFile() {
                let fileInput = $('#file')[0];
                let file = fileInput.files[0];

                if (!file) return;

                // Validasi client-side (tetap perlu validasi server-side)
                if (!validateFile(file)) return;

                // Siapkan form data
                let formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('jadwal_ami_id', '{{ $jadwal->id }}');
                formData.append('file', file);

                // Show loading indicator
                showUploadProgress(file.name);

                $.ajax({
                    url: '{{ route('auditor-upload.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        let xhr = new window.XMLHttpRequest();
                        // Upload progress
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                let percentComplete = (evt.loaded / evt.total) * 100;
                                updateUploadProgress(percentComplete);
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        if (response.success) {
                            showUploadSuccess(response.message);
                            // Reset form setelah 2 detik
                            setTimeout(() => {
                                resetUploadForm();
                                location.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors || {};
                        let message = xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat upload file.';

                        if (Object.keys(errors).length > 0) {
                            let errorText = '';
                            Object.values(errors).forEach(errorArray => {
                                errorArray.forEach(error => {
                                    errorText += error + '\n';
                                });
                            });
                            message = errorText;
                        }

                        showUploadError(message);
                        resetUploadForm();
                    }
                });
            }

            function validateFile(file) {
                // Validasi type
                const allowedTypes = ['application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Format File Tidak Valid!',
                        text: 'File harus berformat PDF, DOC, atau DOCX.'
                    });
                    return false;
                }

                // Validasi ukuran (10MB)
                if (file.size > 10485760) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ukuran File Terlalu Besar!',
                        text: 'Ukuran file maksimal 10MB.'
                    });
                    return false;
                }

                // Validasi nama file untuk karakter berbahaya
                const dangerousChars = /[<>:"/\\|?*]/;
                if (dangerousChars.test(file.name)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Nama File Tidak Valid!',
                        text: 'Nama file mengandung karakter yang tidak diizinkan.'
                    });
                    return false;
                }

                return true;
            }

            function showUploadProgress(fileName) {
                // Ganti form dengan progress indicator
                $('#uploadForm').html(`
            <div class="text-center">
                <div class="mb-3">
                    <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                </div>
                <h5>Mengupload: ${fileName}</h5>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%">0%</div>
                </div>
                <small class="text-muted">Mohon tunggu, file sedang diupload...</small>
            </div>
        `);
            }

            function updateUploadProgress(percent) {
                let progressBar = $('.progress-bar');
                progressBar.css('width', percent + '%');
                progressBar.text(Math.round(percent) + '%');
            }

            function showUploadSuccess(message) {
                $('#uploadForm').html(`
            <div class="text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-3x text-success"></i>
                </div>
                <h5 class="text-success">Upload Berhasil!</h5>
                <p>${message}</p>
                <small class="text-muted">Halaman akan refresh otomatis...</small>
            </div>
        `);
            }

            function showUploadError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Gagal!',
                    text: message,
                    confirmButtonText: 'Coba Lagi'
                });
            }

            function resetUploadForm() {
                // Kembalikan form ke kondisi awal
                location.reload();
            }
        });
    </script>
    @endif {{-- End of JavaScript conditional check --}}
@endpush
