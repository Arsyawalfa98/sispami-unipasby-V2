@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit Dokumen SPMI & AMI') }}</h1>
        <a href="{{ route('dokumen-spmi-ami.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('dokumen-spmi-ami.update', $dokumenSPMIAMI->id) }}" method="POST"
                enctype="multipart/form-data" id="uploadForm">
                @csrf
                @method('PUT')

                <!-- Kategori Dokumen field -->
                <div class="form-group">
                    <label for="kategori_dokumen">Kategori Dokumen <span class="text-danger">*</span></label>
                    <select class="form-control @error('kategori_dokumen') is-invalid @enderror" id="kategori_dokumen"
                        name="kategori_dokumen" required>
                        <option value="">Pilih Kategori</option>
                        @foreach ($kategoriList as $kategori)
                            <option value="{{ $kategori->nama_kategori }}"
                                {{ old('kategori_dokumen', $dokumenSPMIAMI->kategori_dokumen) == $kategori->nama_kategori ? 'selected' : '' }}>
                                {{ $kategori->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Nama Dokumen field -->
                <div class="form-group">
                    <label for="nama_dokumen">Nama Dokumen <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nama_dokumen') is-invalid @enderror" id="nama_dokumen"
                        name="nama_dokumen" value="{{ old('nama_dokumen', $dokumenSPMIAMI->nama_dokumen) }}" required>
                    @error('nama_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- File Upload field -->
                <div class="form-group">
                    <label for="file">File Dokumen</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input @error('file') is-invalid @enderror" id="file"
                            name="file" accept=".pdf,.doc,.docx">
                        <label class="custom-file-label" for="file">Pilih file...</label>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            Format yang diizinkan: PDF, DOC, DOCX (Maks. 10MB)<br>
                            File saat ini: <a href="{{ Storage::url($dokumenSPMIAMI->file_path) }}"
                                target="_blank">{{ basename($dokumenSPMIAMI->file_path) }}</a>
                        </small>
                    </div>
                    <!-- File Preview -->
                    <div id="filePreview" class="mt-2 d-none">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title" id="fileName"></h5>
                                <p class="card-text" id="fileSize"></p>
                                <p class="card-text" id="fileType"></p>
                                <div class="progress mb-3 d-none" id="uploadProgress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm" id="removeFile">
                                    <i class="fas fa-times"></i> Hapus File
                                </button>
                            </div>
                        </div>
                    </div>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Is Active field -->
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                            {{ old('is_active', $dokumenSPMIAMI->is_active) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Aktif</label>
                    </div>
                </div>

                <!-- Submit buttons -->
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="{{ route('dokumen-spmi-ami.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi element
    const fileInput = document.getElementById('file');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    const removeFile = document.getElementById('removeFile');
    const submitBtn = document.getElementById('submitBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = uploadProgress.querySelector('.progress-bar');
    const cancelButton = document.querySelector('a.btn-secondary');
    let isCleaningUp = false;

    // Fungsi helper
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function validateFile(file) {
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'File Tidak Valid',
                text: 'Hanya file PDF dan DOC/DOCX yang diizinkan'
            });
            return false;
        }

        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Terlalu Besar',
                text: 'Ukuran file maksimal adalah 10MB'
            });
            return false;
        }

        const fileName = file.name;
        if (/[^a-zA-Z0-9\s._-]/.test(fileName)) {
            Swal.fire({
                icon: 'error',
                title: 'Nama File Tidak Valid',
                text: 'Nama file mengandung karakter yang tidak diizinkan'
            });
            return false;
        }

        if (fileName.split('.').length > 2) {
            Swal.fire({
                icon: 'error',
                title: 'Nama File Tidak Valid',
                text: 'File dengan ekstensi ganda tidak diizinkan'
            });
            return false;
        }

        return true;
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        uploadProgress.classList.remove('d-none');

        // Tampilkan loading bar
        progressBar.style.width = '50%';
        progressBar.textContent = 'Uploading...';

        fetch('/dokumen-spmi-ami/upload-temp', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            progressBar.style.width = '100%';
            progressBar.textContent = 'Complete';
            
            if (data.success) {
                // Add hidden input for temp file
                const tempInput = document.createElement('input');
                tempInput.type = 'hidden';
                tempInput.name = 'temp_file';
                tempInput.value = data.filename;
                document.getElementById('uploadForm').appendChild(tempInput);

                Swal.fire({
                    icon: 'success',
                    title: 'File Berhasil Diupload',
                    text: 'File siap untuk disimpan',
                    timer: 1500
                });
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Upload Gagal',
                text: error.message || 'Terjadi kesalahan saat upload file'
            });
            resetFileInput();
        });
    }

    function cleanupTemp() {
        return fetch('/dokumen-spmi-ami/cleanup-temp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        });
    }

    function resetFileInput() {
        if (document.querySelector('input[name="temp_file"]')) {
            cleanupTemp();
        }

        fileInput.value = '';
        filePreview.classList.add('d-none');
        uploadProgress.classList.add('d-none');
        progressBar.style.width = '0%';
        progressBar.textContent = '';
        
        // Hanya disable di halaman create
        if (document.querySelector('form[action*="store"]')) {
            submitBtn.disabled = true;
        }
        
        const label = fileInput.nextElementSibling;
        label.textContent = 'Pilih file...';
        const tempInput = document.querySelector('input[name="temp_file"]');
        if (tempInput) tempInput.remove();
    }

    // Event Listeners
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!validateFile(file)) {
            resetFileInput();
            return;
        }

        const label = fileInput.nextElementSibling;
        label.textContent = file.name;

        fileName.textContent = file.name;
        fileSize.textContent = 'Ukuran: ' + formatFileSize(file.size);
        fileType.textContent = 'Tipe: ' + file.type;
        filePreview.classList.remove('d-none');
        submitBtn.disabled = false;

        uploadFile(file);
    });

    removeFile.addEventListener('click', resetFileInput);

    cancelButton.addEventListener('click', function(e) {
        if (document.querySelector('input[name="temp_file"]')) {
            e.preventDefault();
            cleanupTemp()
                .then(() => {
                    window.location.href = cancelButton.href;
                });
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (document.querySelector('input[name="temp_file"]') && !isCleaningUp) {
            e.preventDefault();
            navigator.sendBeacon('/dokumen-spmi-ami/cleanup-temp', new FormData());
            e.returnValue = '';
        }
    });
});
</script>
@endpush
