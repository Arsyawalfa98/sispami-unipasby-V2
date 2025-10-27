@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Upload Dokumen Baru</h1>
        <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
            'kriteriaDokumenId' => $kriteriaDokumen->id,
            'prodi' => $selectedProdi,
        ]) }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="uploadForm" action="{{ route('dokumen-persyaratan-pemenuhan-dokumen.store', $kriteriaDokumen->id) }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="kriteria_dokumen_id" value="{{ $kriteriaDokumen->id }}">

                <!-- Untuk create.blade.php -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="font-weight-bold">Informasi Kriteria:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Kriteria:</strong>
                                    {{ $kriteriaDokumen->judulKriteriaDokumen?->nama_kriteria_dokumen ?? 'Kriteria' }}</p>
                                <p class="mb-1"><strong>Element:</strong> {{ $kriteriaDokumen->element }}</p>
                                <p class="mb-1"><strong>Indikator:</strong> {{ $kriteriaDokumen->indikator }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Tipe Dokumen:</strong>
                                    <span id="tipeDokumenText">
                                        @php
                                            $firstKebutuhan = $kebutuhanDokumen->first();
                                            $tipeDokumenText = '(Belum Di Setting Di Kriteria Dokumen)';
                                            
                                            if($firstKebutuhan && $firstKebutuhan->tipe_dokumen) {
                                                // Cari tipe dokumen berdasarkan ID
                                                $tipeDokumen = App\Models\TipeDokumen::find($firstKebutuhan->tipe_dokumen);
                                                if($tipeDokumen) {
                                                    $tipeDokumenText = $tipeDokumen->nama;
                                                }
                                            }
                                        @endphp
                                        {{ $tipeDokumenText }}
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Keterangan:</strong> <span
                                        id="keteranganText">{{ $kebutuhanDokumen->first()?->keterangan ?? '(Belum Di Setting Di Kriteria Dokumen)' }}</span>
                                </p>
                                <p class="mb-1"><strong>Bobot:</strong> <span
                                    id="bobotText">{{ $kriteriaDokumen->bobot ?? '0' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="form-group">
                    <label>Nama Dokumen</label>
                    <select name="nama_dokumen" class="form-control" required>
                        @foreach ($kebutuhanDokumen as $kebutuhan)
                            <option value="{{ $kebutuhan->nama_dokumen }}" 
                                    data-tipe-id="{{ $kebutuhan->tipe_dokumen }}"
                                    data-keterangan="{{ $kebutuhan->keterangan }}">
                                {{ $kebutuhan->nama_dokumen }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Pilih Jenis Input:</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="inputTypeSwitch">
                        <label class="custom-control-label" for="inputTypeSwitch">
                            <span id="switchLabel">Upload File</span>
                        </label>
                    </div>
                    <small class="text-muted">
                        <span id="switchDescription">Mode saat ini: Upload file dokumen</span><br>
                        <span id="switchDescription">ganti jika ingin input file manual atau dari capaian renop</span>
                    </small>
                </div>

                <!-- File Upload Section -->
                <div id="fileSection">
                    <div class="form-group">
                        <label>File</label>
                        <input type="file" name="file" class="form-control" accept=".pdf,.docx,.doc">
                        <small class="text-muted">Format: PDF, DOCX, DOC. Maksimal 5MB</small>
                    </div>
                </div>
                        
                <!-- Renop Selection Section (Hidden by default) -->
                {{-- <div id="renopSection" style="display: none;">
                    <div class="form-group">
                        <label>Pilih Renop</label>
                        
                        <select name="renop" class="form-control">
                            @foreach($datarenop as $data)
                                            <option value="">{{ $data->name }}</option>
                                        @endforeach
                        </select>
                    </div>
                </div> --}}

                <div class="form-group">
                    <label>Tambahan Informasi</label>
                    <textarea name="tambahan_informasi" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="btnUpload">
                    <i class="fas fa-upload"></i> Upload Dokumen
                </button>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Simpan data tipe dokumen dalam variabel JavaScript
        const tipeDokumenData = @json(App\Models\TipeDokumen::all());
        
        $(document).ready(function() {
            let isFileUploaded = false;

            // Handler untuk file input change
            $('input[name="file"]').on('change', function() {
                const file = this.files[0];
                if (!file) return;

                // Validasi tipe file
                const allowedTypes = ['application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                const fileType = file.type;

                if (!allowedTypes.includes(fileType)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Format File Tidak Valid',
                        text: 'Hanya file PDF, DOC, dan DOCX yang diperbolehkan'
                    });
                    this.value = '';
                    return;
                }

                // Validasi ukuran file (5MB = 5 * 1024 * 1024 bytes)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ukuran File Terlalu Besar',
                        text: 'Ukuran file maksimal adalah 5MB'
                    });
                    this.value = '';
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('kriteria_dokumen_id', $('input[name="kriteria_dokumen_id"]').val());

                Swal.fire({
                    title: 'Uploading...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                        url: '{{ route('pemenuhan-dokumen.upload-file') }}',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                    .done(function(response) {
                        if (response.success) {
                            isFileUploaded = true;
                            Swal.close();
                        }
                    })
                    .fail(function(xhr) {
                        isFileUploaded = false;
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Gagal mengupload file'
                        });
                        this.value = '';
                    });
            });

            // Handler untuk form submit
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();

                if (!isFileUploaded) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Silakan upload file terlebih dahulu'
                    });
                    return;
                }

                const formData = new FormData(this);

                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                    .done(function(response) {
                        if (response.success) {
                            const isLastFile = response.totalUploaded >= response.totalNeeded;

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: isLastFile ?
                                    'Dokumen berhasil disimpan. Semua file untuk kriteria ini sudah terpenuhi.' :
                                    `Dokumen berhasil disimpan. Masih ada ${response.totalNeeded - response.totalUploaded} file yang harus diupload.`,
                                showCancelButton: true,
                                confirmButtonText: isLastFile ? 'Selesai' : 'Upload File Lain',
                                cancelButtonText: 'Kembali ke Daftar'
                            }).then((result) => {
                                if (result.isConfirmed && !isLastFile) {
                                    $('#uploadForm')[0].reset();
                                    isFileUploaded = false;
                                } else {
                                    window.location.href =
                                        "{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', ['kriteriaDokumenId' => $kriteriaDokumen->id, 'prodi' => $selectedProdi]) }}";
                                }
                            });
                        }
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat menyimpan data'
                        });
                    });
            });

            // PERBAIKAN: Gunakan data atribut untuk menangani perubahan dropdown
            $('select[name="nama_dokumen"]').on('change', function() {
                // Ambil data dari atribut option yang dipilih
                const selectedOption = $(this).find('option:selected');
                const tipeId = selectedOption.data('tipe-id');
                const keterangan = selectedOption.data('keterangan');
                
                // Ambil data bobot dari kriteria dokumen (tidak berubah)
                const kriteriaDokumen = {!! json_encode($kriteriaDokumen) !!};
                
                // Cari tipe dokumen berdasarkan ID
                let tipeDokumenNama = '(Belum Di Setting Di Kriteria Dokumen)';
                if (tipeId) {
                    const tipeDokumen = tipeDokumenData.find(t => t.id == tipeId);
                    if (tipeDokumen) {
                        tipeDokumenNama = tipeDokumen.nama;
                    }
                }
                
                // Update teks di UI
                $('#tipeDokumenText').text(tipeDokumenNama);
                $('#keteranganText').text(keterangan || '(Belum Di Setting Di Kriteria Dokumen)');
                $('#bobotText').text(kriteriaDokumen.bobot || '0');
            });
            $('#inputTypeSwitch').on('change', function() {
                const isChecked = $(this).is(':checked');
                
                if (isChecked) {
                    // Switch to Renop mode
                    $('#fileSection').hide();
                    $('#renopSection').show();
                    $('#switchLabel').text('Input Renop');
                    $('#switchDescription').text('Mode saat ini: Input nomor renop');
                    
                    // Clear file input and reset upload status
                    $('input[name="file"]').val('');
                    isFileUploaded = false;
                    
                    // Make renop required and file not required
                    $('select[name="renop"]').prop('required', true);
                    $('input[name="file"]').prop('required', false);
                } else {
                    // Switch to File mode
                    $('#fileSection').show();
                    $('#renopSection').hide();
                    $('#switchLabel').text('Upload File');
                    $('#switchDescription').text('Mode saat ini: Upload file dokumen');
                    
                    // Clear renop selection
                    $('select[name="renop"]').val('');
                    
                    // Make file required and renop not required
                    $('input[name="file"]').prop('required', true);
                    $('select[name="renop"]').prop('required', false);
                }
            });
        });
    </script>
@endpush
