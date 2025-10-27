@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Jadwal AMI</h1>
        <a href="{{ route('jadwal-ami.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="jadwalAmiForm" method="POST" action="{{ route('jadwal-ami.update', $jadwalAmi->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group position-relative">
                            <label>Program Studi</label>
                            @php
                            // Bersihkan string "undefined" dari prodi jika ada
                            $prodiDisplay = $jadwalAmi->prodi;
                            if(strpos($prodiDisplay, 'undefined - ') === 0) {
                                $prodiDisplay = str_replace('undefined - ', '', $prodiDisplay);
                            }
                            @endphp
                            <input type="text" class="form-control program-studi-input"
                                placeholder="Ketik untuk mencari Program Studi" value="{{ $prodiDisplay }}"
                                autocomplete="off">
                            <div class="dropdown-menu w-100 p-0" id="searchResults"
                                style="max-height: 200px; overflow-y: auto;">
                                <!-- Hasil pencarian akan muncul di sini -->
                            </div>
                            <input type="hidden" name="prodi" value="{{ $prodiDisplay }}">
                            <div class="invalid-feedback program-studi-error"></div>
                        </div>

                        <div class="form-group">
                            <label>Fakultas</label>
                            <input type="text" class="form-control" id="fakultas" name="fakultas"
                                value="{{ $jadwalAmi->fakultas }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Standar Akreditasi</label>
                            <select name="standar_akreditasi" class="form-control" id="standar_akreditasi" required>
                                <option value="">Pilih Standar Akreditasi</option>
                                @php
                                    // Ambil tahun dari periode jadwal AMI yang ada
                                    $periodeYear = substr($jadwalAmi->periode, 0, 4);
                                @endphp
                                
                                @foreach ($lembagaAkreditasi as $lembaga)
                                    @php
                                        // Cek apakah standar akreditasi dan tahun sesuai dengan data jadwal
                                        $isSelected = ($jadwalAmi->standar_akreditasi == $lembaga->nama && 
                                                      $periodeYear == $lembaga->tahun);
                                    @endphp
                                    <option value="{{ $lembaga->nama }}" 
                                        data-tahun="{{ $lembaga->tahun }}"
                                        data-id="{{ $lembaga->id }}"
                                        {{ $isSelected ? 'selected' : '' }}>
                                        {{ $lembaga->nama }} - {{ $lembaga->tahun }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Periode</label>
                            <!-- Field yang terlihat user -->
                            <input type="text" name="periode_display" id="periode_display" class="form-control"
                                placeholder="Terisi Ketika Memilih Standart Akreditasi" readonly
                                value="{{ substr($jadwalAmi->periode, 0, 4) }}">
                            <!-- Field tersembunyi untuk dikirim ke server -->
                            <input type="hidden" name="periode" id="periode_hidden" value="{{ $jadwalAmi->periode }}">
                        </div>

                        <div class="form-group">
                            <label>Tanggal Mulai AMI</label>
                            <input type="datetime-local" name="tanggal_mulai" class="form-control" required
                                value="{{ date('Y-m-d\TH:i', strtotime($jadwalAmi->tanggal_mulai)) }}">
                        </div>

                        <div class="form-group">
                            <label>Tanggal Selesai AMI</label>
                            <input type="datetime-local" name="tanggal_selesai" class="form-control" required
                                value="{{ date('Y-m-d\TH:i', strtotime($jadwalAmi->tanggal_selesai)) }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Ketua Auditor -->
                        @php
                            $ketuaAuditor = $jadwalAmi->timAuditor->where('pivot.role_auditor', 'ketua')->first();
                            $anggotaAuditor = $jadwalAmi->timAuditor->where('pivot.role_auditor', 'anggota')->pluck('id')->toArray();
                        @endphp

                        <div class="form-group">
                            <label><i class="fas fa-crown text-warning"></i> Ketua Auditor <span class="text-danger">*</span></label>
                            <select name="ketua_auditor" class="form-control select2-ketua" required>
                                <option value="">Pilih Ketua Auditor</option>
                                @foreach ($auditors as $auditor)
                                    <option value="{{ $auditor->id }}"
                                        {{ $ketuaAuditor && $ketuaAuditor->id == $auditor->id ? 'selected' : '' }}>
                                        {{ $auditor->name }} ({{ $auditor->username }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Ketua auditor wajib dipilih</small>
                        </div>

                        <!-- Anggota Auditor -->
                        <div class="form-group">
                            <label><i class="fas fa-users text-info"></i> Anggota Auditor <span class="text-muted">(Opsional)</span></label>
                            <select name="anggota_auditor[]" class="form-control select2-anggota" multiple>
                                @foreach ($auditors as $auditor)
                                    <option value="{{ $auditor->id }}"
                                        {{ in_array($auditor->id, $anggotaAuditor) ? 'selected' : '' }}>
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
                                        <input type="checkbox" class="custom-control-input" id="upload_enabled" name="upload_enabled" value="1"
                                            {{ $jadwalAmi->upload_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="upload_enabled">
                                            <strong>Aktifkan Upload untuk Auditor</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Centang untuk mengizinkan auditor upload file</small>
                                </div>

                                <div id="upload-schedule-fields" style="{{ $jadwalAmi->upload_enabled ? 'display: block;' : 'display: none;' }}">
                                    <div class="form-group">
                                        <label>Tanggal Mulai Upload</label>
                                        <input type="datetime-local" name="upload_mulai" class="form-control" step="1"
                                            value="{{ $jadwalAmi->upload_mulai ? date('Y-m-d\TH:i', strtotime($jadwalAmi->upload_mulai)) : '' }}"
                                            {{ $jadwalAmi->upload_enabled ? 'required' : '' }}>
                                        <small class="form-text text-muted">Kapan auditor mulai bisa upload file</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Tanggal Selesai Upload</label>
                                        <input type="datetime-local" name="upload_selesai" class="form-control" step="1"
                                            value="{{ $jadwalAmi->upload_selesai ? date('Y-m-d\TH:i', strtotime($jadwalAmi->upload_selesai)) : '' }}"
                                            {{ $jadwalAmi->upload_enabled ? 'required' : '' }}>
                                        <small class="form-text text-muted">Kapan batas akhir upload file</small>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label>Keterangan Upload <span class="text-muted">(Opsional)</span></label>
                                        <textarea name="upload_keterangan" class="form-control" rows="3" 
                                            placeholder="Catatan atau instruksi khusus untuk auditor..." maxlength="500">{{ $jadwalAmi->upload_keterangan }}</textarea>
                                        <small class="form-text text-muted">Maksimal 500 karakter</small>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Catatan:</strong> Jadwal upload harus berada dalam rentang jadwal AMI.
                                        @if($jadwalAmi->hasUploadedFiles())
                                            <br><strong>Perhatian:</strong> Jadwal ini sudah memiliki {{ $jadwalAmi->uploaded_files_count }} file upload.
                                            Perubahan jadwal dapat mempengaruhi akses auditor.
                                        @endif
                                    </small>
                                </div>

                                {{-- Upload Status Info --}}
                                @if($jadwalAmi->upload_enabled)
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small>
                                            <strong>Status Upload:</strong> 
                                            <span class="badge {{ $jadwalAmi->upload_status_badge }}">{{ $jadwalAmi->upload_status }}</span>
                                            @if($jadwalAmi->hasUploadedFiles())
                                                <br><strong>Total File:</strong> {{ $jadwalAmi->uploaded_files_count }} file(s)
                                            @endif
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="{{ route('jadwal-ami.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
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

        .bg-light {
            background-color: #f8f9fa !important;
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
            let selectedProdi = $input.val();

            // AUTOCOMPLETE PROGRAM STUDI
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
                                            prodiDisplay = item.kode + ' - ' + item.text;
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
                $('input[name="prodi"]').val(prodiValue);
                
                const fakultas = $(this).data('fakultas') || '';
                $('#fakultas').val(fakultas);
                $searchResults.hide();
                $input.removeClass('is-invalid');
            });

            // STANDAR AKREDITASI & PERIODE HANDLING
            $('#standar_akreditasi').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    const tahun = selectedOption.data('tahun');
                    const lastDigit = $('#periode_hidden').val().slice(-1) || '1'; // Ambil digit terakhir atau default ke '1'
                    
                    // Update nilai periode_display dengan tahun yang dipilih
                    $('#periode_display').val(tahun);
                    
                    // Update nilai periode_hidden dengan tahun + digit semester
                    $('#periode_hidden').val(tahun + lastDigit);
                } else {
                    $('#periode_display').val('');
                    $('#periode_hidden').val('');
                }
            });

            // Prevent duplicate selection (ketua dan anggota tidak boleh sama)
            let previousKetuaId = $('.select2-ketua').val(); // Simpan ketua awal
            
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

            // Warning jika ada uploaded files dan mengubah jadwal
            const hasUploadedFiles = {{ $jadwalAmi->hasUploadedFiles() ? 'true' : 'false' }};
            
            if (hasUploadedFiles) {
                $('input[name="upload_mulai"], input[name="upload_selesai"], #upload_enabled').change(function() {
                    if (!$(this).data('warned')) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Perhatian!',
                            text: 'Jadwal ini sudah memiliki file upload. Perubahan dapat mempengaruhi akses auditor.',
                            confirmButtonText: 'Mengerti'
                        });
                        $(this).data('warned', true);
                    }
                });
            }

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $searchResults.hide();
                }
            });
            
            // Trigger standar_akreditasi change to ensure periode is properly set
            $('#standar_akreditasi').trigger('change');
            
            // Trigger ketua selection to disable in anggota
            $('.select2-ketua').trigger('change');

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