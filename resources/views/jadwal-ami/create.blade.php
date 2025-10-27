@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Jadwal AMI</h1>
        <a href="{{ route('jadwal-ami.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="jadwalAmiForm" action="{{ route('jadwal-ami.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group position-relative">
                            <label>Program Studi</label>
                            <input type="text" class="form-control program-studi-input"
                                placeholder="Ketik untuk mencari Program Studi" autocomplete="off">
                            <div class="dropdown-menu w-100 p-0" id="searchResults"
                                style="max-height: 200px; overflow-y: auto;">
                                <!-- Hasil pencarian akan muncul di sini -->
                            </div>
                            <div class="invalid-feedback program-studi-error"></div>
                        </div>

                        <div class="form-group">
                            <label>Fakultas</label>
                            <input type="text" class="form-control" id="fakultas" name="fakultas" readonly>
                        </div>

                        <div class="form-group">
                            <label>Standar Akreditasi</label>
                            <select name="standar_akreditasi" class="form-control" required>
                                <option value="">Pilih Standar Akreditasi</option>
                                @foreach ($lembagaAkreditasi as $lembaga)
                                    <option value="{{ $lembaga->nama }}" data-tahun="{{ $lembaga->tahun }}">
                                        {{ $lembaga->nama }} - {{ $lembaga->tahun }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        <div class="form-group">
                            <label>Periode/Tahun</label>
                            <!-- Field yang terlihat user -->
                            <input type="text" name="periode_display" class="form-control"
                                placeholder="Terisi Ketika Memilih Standart Akreditasi" readonly>
                            <!-- Field tersembunyi untuk dikirim ke server -->
                            <input type="hidden" name="periode" value="">
                        </div>

                        <div class="form-group">
                            <label>Tanggal Mulai AMI</label>
                            <input type="datetime-local" name="tanggal_mulai" class="form-control" required step="1">
                        </div>

                        <div class="form-group">
                            <label>Tanggal Selesai AMI</label>
                            <input type="datetime-local" name="tanggal_selesai" class="form-control" required
                                step="1">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Ketua Auditor -->
                        <div class="form-group">
                            <label><i class="fas fa-crown text-warning"></i> Ketua Auditor <span
                                    class="text-danger">*</span></label>
                            <select name="ketua_auditor" class="form-control select2-ketua" required>
                                <option value="">Pilih Ketua Auditor</option>
                                @foreach ($auditors as $auditor)
                                    <option value="{{ $auditor->id }}">
                                        {{ $auditor->name }} ({{ $auditor->username }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Ketua auditor wajib dipilih</small>
                        </div>

                        <!-- Anggota Auditor -->
                        <div class="form-group">
                            <label><i class="fas fa-users text-info"></i> Anggota Auditor <span
                                    class="text-muted">(Opsional)</span></label>
                            <select name="anggota_auditor[]" class="form-control select2-anggota" multiple>
                                @foreach ($auditors as $auditor)
                                    <option value="{{ $auditor->id }}">
                                        {{ $auditor->name }} ({{ $auditor->username }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Pilih anggota auditor (dapat dikosongkan)</small>
                        </div>

                        {{-- TAMBAHAN BARU: UPLOAD SCHEDULE SECTION --}}
                        <div class="card border-info mt-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-upload"></i> Jadwal Upload Auditor</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="upload_enabled"
                                            name="upload_enabled" value="1">
                                        <label class="custom-control-label" for="upload_enabled">
                                            <strong>Aktifkan Upload untuk Auditor</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Centang untuk mengizinkan auditor upload
                                        file</small>
                                </div>

                                <div id="upload-schedule-fields" style="display: none;">
                                    <div class="form-group">
                                        <label>Tanggal Mulai Upload</label>
                                        <input type="datetime-local" name="upload_mulai" class="form-control"
                                            step="1">
                                        <small class="form-text text-muted">Kapan auditor mulai bisa upload file</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Tanggal Selesai Upload</label>
                                        <input type="datetime-local" name="upload_selesai" class="form-control"
                                            step="1">
                                        <small class="form-text text-muted">Kapan batas akhir upload file</small>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label>Keterangan Upload <span class="text-muted">(Opsional)</span></label>
                                        <textarea name="upload_keterangan" class="form-control" rows="3"
                                            placeholder="Catatan atau instruksi khusus untuk auditor..." maxlength="500"></textarea>
                                        <small class="form-text text-muted">Maksimal 500 karakter</small>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <small>
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Catatan:</strong> Jadwal upload harus berada dalam rentang jadwal AMI.
                                        Auditor hanya dapat upload file sesuai jadwal yang ditetapkan.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <button type="button" class="btn btn-secondary"
                        onclick="window.location.href='{{ route('jadwal-ami.index') }}'">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .dropdown-menu {
            margin-top: 0;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .select2-container {
            width: 100% !important;
        }

        .position-relative {
            position: relative !important;
        }

        #searchResults {
            position: absolute;
            width: 100%;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
        }

        .form-group label i {
            margin-right: 5px;
        }

        .card-header.bg-info {
            background-color: #36b9cc !important;
        }

        .border-info {
            border-color: #36b9cc !important;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2-ketua').select2({
                placeholder: 'Pilih Ketua Auditor',
                width: '100%'
            });

            $('.select2-anggota').select2({
                placeholder: 'Pilih Anggota Auditor (Opsional)',
                width: '100%'
            });

            let typingTimer;
            const $input = $('.program-studi-input');
            const $searchResults = $('#searchResults');
            let selectedProdi = null;

            // Program Studi Autocomplete
            $input.on('input', function() {
                clearTimeout(typingTimer);
                const searchTerm = $(this).val();

                if (searchTerm.length >= 3) {
                    typingTimer = setTimeout(() => {
                        $.get('/prodi/search', {
                                search: searchTerm
                            })
                            .done(function(data) {
                                $searchResults.empty();
                                if (data.length > 0) {
                                    data.forEach(item => {
                                        let prodiDisplay = item.text;
                                        if (item.kode) {
                                            prodiDisplay = item.kode + ' - ' + item
                                                .text;
                                        }

                                        $searchResults.append(`
                                        <button type="button" class="dropdown-item" 
                                            data-kode="${item.kode || ''}"
                                            data-nama="${item.text || ''}"
                                            data-fakultas="${item.fakultas ? item.fakultas.text : ''}">
                                            ${prodiDisplay}
                                        </button>
                                    `);
                                    });
                                    $searchResults.show();
                                }
                            });
                    }, 300);
                } else {
                    $searchResults.hide().empty();
                }
            });

            $(document).on('click', '.dropdown-item', function() {
                const kode = $(this).data('kode') || '';
                const nama = $(this).data('nama') || '';

                let prodiValue = nama;
                if (kode && kode.trim() !== '') {
                    prodiValue = kode + ' - ' + nama;
                }

                selectedProdi = prodiValue;
                $input.val(prodiValue);

                if ($('#prodi').length === 0) {
                    $input.after('<input type="hidden" name="prodi" id="prodi" value="' + prodiValue +
                        '">');
                } else {
                    $('#prodi').val(prodiValue);
                }

                const fakultas = $(this).data('fakultas') || '';
                $('#fakultas').val(fakultas);
                $searchResults.hide();
                $input.removeClass('is-invalid');
            });

            // Standar Akreditasi Change
            $('select[name="standar_akreditasi"]').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const tahun = selectedOption.data('tahun');

                if (tahun) {
                    $('input[name="periode_display"]').val(tahun);
                    $('input[name="periode"]').val(tahun + '1'); // default semester 1
                } else {
                    $('input[name="periode_display"]').val('');
                    $('input[name="periode"]').val('');
                }
            });


            // Prevent duplicate selection (ketua dan anggota tidak boleh sama)
            let previousKetuaId = null; // Simpan ketua sebelumnya

            $('.select2-ketua').on('change', function() {
                const ketuaId = $(this).val();
                const $anggotaSelect = $('.select2-anggota');

                // Re-enable ketua sebelumnya jika ada
                if (previousKetuaId) {
                    $anggotaSelect.find(`option[value="${previousKetuaId}"]`).prop('disabled', false);
                }

                // Disable ketua yang baru dipilih dari anggota selection
                if (ketuaId) {
                    $anggotaSelect.find(`option[value="${ketuaId}"]`).prop('disabled', true);

                    // Remove ketua dari anggota selection jika sudah terpilih
                    let currentAnggota = $anggotaSelect.val() || [];
                    currentAnggota = currentAnggota.filter(id => id !== ketuaId);
                    $anggotaSelect.val(currentAnggota).trigger('change');

                    // Update ketua sebelumnya
                    previousKetuaId = ketuaId;
                } else {
                    // Jika tidak ada ketua dipilih, enable semua options
                    $anggotaSelect.find('option').prop('disabled', false);
                    previousKetuaId = null;
                }

                // Refresh select2
                $anggotaSelect.select2('destroy').select2({
                    placeholder: 'Pilih Anggota Auditor (Opsional)',
                    width: '100%'
                });
            });

            $('.select2-anggota').on('change', function() {
                const anggotaIds = $(this).val() || [];
                const ketuaId = $('.select2-ketua').val();

                // Remove ketua from anggota if accidentally selected
                if (ketuaId && anggotaIds.includes(ketuaId)) {
                    const filteredAnggota = anggotaIds.filter(id => id !== ketuaId);
                    $(this).val(filteredAnggota).trigger('change');

                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian!',
                        text: 'Ketua auditor tidak bisa dipilih sebagai anggota auditor'
                    });
                }
            });

            // ===== UPLOAD SCHEDULE HANDLING =====

            // Toggle upload schedule fields
            $('#upload_enabled').change(function() {
                if ($(this).is(':checked')) {
                    $('#upload-schedule-fields').slideDown();
                    // Set required attributes
                    $('input[name="upload_mulai"]').attr('required', true);
                    $('input[name="upload_selesai"]').attr('required', true);
                } else {
                    $('#upload-schedule-fields').slideUp();
                    // Remove required attributes
                    $('input[name="upload_mulai"]').removeAttr('required');
                    $('input[name="upload_selesai"]').removeAttr('required');
                    // Clear values
                    $('input[name="upload_mulai"]').val('');
                    $('input[name="upload_selesai"]').val('');
                    $('textarea[name="upload_keterangan"]').val('');
                }
            });


            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $searchResults.hide();
                }
            });
        });
    </script>
@endpush
