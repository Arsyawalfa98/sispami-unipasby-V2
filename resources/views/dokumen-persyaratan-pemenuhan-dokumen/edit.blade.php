@extends('layouts.admin')
@php
    use App\Models\PenilaianKriteria;
@endphp
@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Dokumen</h1>
        <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.index', [
            'kriteriaDokumenId' => $dokumen->kriteria_dokumen_id,
            'prodi' => $dokumen->prodi,
        ]) }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="editForm" action="{{ route('dokumen-persyaratan-pemenuhan-dokumen.update', $dokumen->id) }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="kriteria_dokumen_id" value="{{ $dokumen->kriteria_dokumen_id }}">

                <!-- Untuk edit.blade.php -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="font-weight-bold">Informasi Kriteria:</h6>
                        <h6 class="font-weight-bold">{{ $dokumen->prodi }} - {{ $dokumen->kriteria }} - Periode Jadwal Ami :
                            {{ $MengambilDataJadwal->periode }} - Jenjang : {{ $dokumen->kriteriaDokumen->jenjang->nama }}
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Kriteria:</strong> {{ $dokumen->kriteria }}</p>
                                <p class="mb-1"><strong>Element:</strong> {{ $dokumen->element }}</p>
                                <p class="mb-1"><strong>Indikator:</strong> {{ $dokumen->indikator }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Tipe Dokumen:</strong>
                                    {{ $dokumen->tipeDokumen?->nama ?? '(Belum Di Setting Di Kriteria Dokumen)' }}
                                </p>
                                <p class="mb-1"><strong>Keterangan:</strong>
                                    {{ $dokumen->keterangan ?? '(Belum Di Setting Di Kriteria Dokumen)' }}</p>
                                <p class="mb-1"><strong>Bobot Kebutuhan:</strong>
                                    {{ $bobot ?? '0' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Dokumen</label>
                    <input type="text" class="form-control" value="{{ $dokumen->nama_dokumen }}" readonly>
                </div>

                <div class="form-group">
                    <label>File</label>
                    @if ($dokumen->file)
                        <div class="mb-2">
                            <a href="{{ Storage::url('pemenuhan_dokumen/' . $dokumen->file) }}"
                                class="btn btn-sm btn-warning" target="_blank">
                                <i class="fas fa-download"></i> File Saat Ini
                            </a>
                        </div>
                    @endif

                    @php
                        $user = auth()->user();
                        $isAdmin = Auth::user()->hasActiveRole('Admin Prodi') || Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM') || Auth::user()->hasActiveRole('Admin PPG');
                        $isAuditor = Auth::user()->hasActiveRole('Auditor') || Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM');
                        
                        // Cek status dari PenilaianKriteria jika ada
                        $penilaianStatus = null;
                        if (isset($penilaianKriteria)) {
                            $penilaianStatus = $penilaianKriteria->status;
                        }

                        $disabledStatuses = [
                            PenilaianKriteria::STATUS_PENILAIAN,
                            PenilaianKriteria::STATUS_DIAJUKAN,
                            PenilaianKriteria::STATUS_DISETUJUI,
                        ];
                    @endphp

                    @if ($penilaianStatus && in_array($penilaianStatus, $disabledStatuses))
                        <small class="text-muted">UPLOAD DOKUMEN TIDAK DAPAT DI LAKUKAN KARNA STATUS DALAM
                            PENILAIAN, DIAJUKAN, DISETUJUI</small>
                    @else
                        @if ($isAdmin)
                            <input type="file" name="file" class="form-control" accept=".pdf,.docx,.doc">
                            <small class="text-muted">Format: PDF, DOCX, DOC Maksimal 5MB</small>
                        @else
                            {{-- tidak dapat melakukan apapun --}}
                        @endif
                    @endif
                </div>
                <div class="form-group">
                    <label>Tambahan Informasi</label>
                    <textarea name="tambahan_informasi" class="form-control" rows="3" {{ $isAuditor ? 'readonly' : '' }}>{{ $dokumen->tambahan_informasi }}</textarea>
                </div>
                @if (
                    $penilaianKriteria &&
                        in_array($penilaianStatus, [
                            PenilaianKriteria::STATUS_DIAJUKAN,
                            PenilaianKriteria::STATUS_REVISI,
                        ]))
                    <div class="alert alert-info">
                        <h5>Informasi Penilaian</h5>
                        <p>Penilaian untuk kriteria ini dapat dilakukan melalui halaman penilaian kriteria.</p>
                        <a href="{{ route('penilaian-kriteria.index', [
                            'kriteriaDokumenId' => $dokumen->kriteria_dokumen_id,
                            'prodi' => $dokumen->prodi,
                        ]) }}"
                            class="btn btn-warning btn-sm">
                            <i class="fas fa-star"></i> Ke Halaman Penilaian
                        </a>
                    </div>
                @endif
                @if ($isAdmin)
                    @if ($allowEdit)
                        <button type="submit" class="btn btn-primary" id="btnUpdate">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    @endif
                @else
                @endif
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
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
            $('#editForm').on('submit', function(e) {
                e.preventDefault();

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
                        Swal.close();

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                showCancelButton: true,
                                confirmButtonText: 'Edit Lagi',
                                cancelButtonText: 'Kembali ke Daftar',
                                allowOutsideClick: false,
                                reverseButtons: true
                            }).then((result) => {
                                if (result.dismiss === Swal.DismissReason.cancel) {
                                    window.location.href = response.redirect_url;
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message
                            });
                        }
                    })
                    .fail(function(xhr) {
                        Swal.close();

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat memperbarui data'
                        });
                    });
            });
        });
    </script>
@endpush
