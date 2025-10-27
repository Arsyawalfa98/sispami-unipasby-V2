@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Program Studi</h1>
        @if (auth()->user()->hasPermission('create-program-studi'))
            <button class="btn btn-primary btn-sm" id="btn-add-row">
                <i class="fas fa-plus"></i> Add new Program Studi
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <!-- Form pencarian dan filter -->
    <form id="search-form" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <select name="jenjang" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Jenjang</option>
                    @foreach ($jenjangList as $jenjang)
                        <option value="{{ $jenjang->nama }}" {{ request('jenjang') == $jenjang->nama ? 'selected' : '' }}>
                            {{ $jenjang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari program studi atau fakultas..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="{{ route('program-studi.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="programStudiTable">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th>Program Studi</th>
                            <th>Jenjang</th>
                            <th>Fakultas</th>
                            <th>Status Akreditasi</th>
                            <th>Tanggal Kadarluarsa</th>
                            <th>Bukti Akreditasi</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programStudi as $index => $item)
                            <tr data-id="{{ $item->id }}">
                                <td>{{ $index + 1 }}</td>
                                <td class="editable" data-field="nama_prodi">
                                    <span class="view-mode">{{ $item->nama_prodi }}</span>
                                    <div class="edit-mode d-none">
                                        <div class="position-relative">
                                            <input type="text" class="form-control prodi-search"
                                                value="{{ $item->nama_prodi }}"
                                                placeholder="Ketik untuk mencari Program Studi">
                                            <div class="dropdown-menu search-results">
                                                <!-- hasil pencarian akan muncul di sini -->
                                            </div>
                                            <input type="hidden" name="nama_prodi" class="prodi-value"
                                                value="{{ $item->nama_prodi }}">
                                        </div>
                                    </div>
                                </td>
                                <td class="editable" data-field="jenjang">
                                    <span class="view-mode">{{ $item->jenjang }}</span>
                                    <div class="edit-mode d-none">
                                        <select class="form-control">
                                            <option value="">Pilih Jenjang</option>
                                            @foreach ($jenjangList as $jenjang)
                                                <option value="{{ $jenjang->nama }}"
                                                    {{ $item->jenjang == $jenjang->nama ? 'selected' : '' }}>
                                                    {{ $jenjang->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="editable" data-field="fakultas">
                                    <span class="view-mode">{{ $item->fakultas }}</span>
                                    <div class="edit-mode d-none">
                                        <input type="text" class="form-control fakultas-input" readonly
                                            value="{{ $item->fakultas }}">
                                    </div>
                                </td>
                                <td class="editable" data-field="status_akreditasi">
                                    <span class="view-mode">{{ $item->status_akreditasi }}</span>
                                    <div class="edit-mode d-none">
                                        <input type="text" class="form-control" value="{{ $item->status_akreditasi }}">
                                    </div>
                                </td>
                                <td class="editable" data-field="tanggal_kadarluarsa">
                                    <span
                                        class="view-mode">{{ $item->tanggal_kadarluarsa ? $item->tanggal_kadarluarsa->format('Y-m-d') : '' }}</span>
                                    <div class="edit-mode d-none">
                                        <input type="date" class="form-control"
                                            value="{{ $item->tanggal_kadarluarsa ? $item->tanggal_kadarluarsa->format('Y-m-d') : '' }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="view-mode">
                                        @if ($item->bukti)
                                            <a href="{{ Storage::url('bukti_akreditasi/' . $item->bukti) }}"
                                                target="_blank">Lihat Bukti</a>
                                        @endif
                                    </div>
                                    <div class="edit-mode d-none">
                                        @if ($item->bukti)
                                            <a href="{{ Storage::url('bukti_akreditasi/' . $item->bukti) }}"
                                                target="_blank" class="current-file">File saat ini</a><br />
                                        @endif
                                        <input type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                </td>
                                <td>
                                    <div class="view-mode">
                                        @if (auth()->user()->hasPermission('edit-program-studi'))
                                            <button class="btn btn-primary btn-sm btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if (auth()->user()->hasPermission('delete-program-studi'))
                                            <button class="btn btn-danger btn-sm btn-delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="edit-mode d-none">
                                        <button class="btn btn-success btn-sm btn-save">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="btn btn-secondary btn-sm btn-cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $programStudi->withQueryString()->links() }}
            </div>
        </div>
    </div>
    <!-- Template untuk baris baru -->
    <template id="new-row-template">
        <tr data-id="new">
            <td></td>
            <td class="editable" data-field="nama_prodi">
                <span class="view-mode d-none"></span>
                <div class="edit-mode">
                    <div class="position-relative">
                        <input type="text" class="form-control prodi-search"
                            placeholder="Ketik untuk mencari Program Studi">
                        <div class="dropdown-menu search-results"></div>
                        <input type="hidden" name="nama_prodi" class="prodi-value">
                    </div>
                </div>
            </td>
            <td class="editable" data-field="jenjang">
                <span class="view-mode d-none"></span>
                <div class="edit-mode">
                    <select class="form-control" required>
                        <option value="">Pilih Jenjang</option>
                        @foreach ($jenjangList as $jenjang)
                            <option value="{{ $jenjang->nama }}">{{ $jenjang->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </td>
            <td class="editable" data-field="fakultas">
                <span class="view-mode d-none"></span>
                <div class="edit-mode">
                    <input type="text" class="form-control fakultas-input" readonly>
                </div>
            </td>
            <td class="editable" data-field="status_akreditasi">
                <span class="view-mode d-none"></span>
                <div class="edit-mode">
                    <input type="text" class="form-control">
                </div>
            </td>
            <td class="editable" data-field="tanggal_kadarluarsa">
                <span class="view-mode d-none"></span>
                <div class="edit-mode">
                    <input type="date" class="form-control">
                </div>
            </td>
            <td>
                <div class="edit-mode">
                    <input type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </td>
            <td>
                <div class="edit-mode">
                    <button class="btn btn-success btn-sm btn-save-new">
                        <i class="fas fa-save"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm btn-cancel">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        </tr>
    </template>
@endsection
@push('css')
    <style>
        /* Reset styles */
        .table-responsive {
            overflow: visible !important;
        }

        .card {
            overflow: visible !important;
        }

        .card-body {
            overflow: visible !important;
        }

        .table td {
            overflow: visible !important;
        }

        /* Dropdown styles */
        .dropdown-wrapper {
            position: relative !important;
        }

        .dropdown-menu.search-results {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            padding: 0;
            margin: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
            list-style: none;
        }

        .search-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .search-item:hover {
            background-color: #f8f9fc;
        }
    </style>
@endpush
@push('js')
    <script>
        $(document).ready(function() {
            let currentUpload = null; // Untuk tracking upload yang sedang berjalan

            function initFileUpload() {
                // Handler untuk file upload - PERBAIKAN
                $(document).on('change', 'input[type="file"]', function() {
                    const fileInput = $(this);
                    const row = fileInput.closest('tr');
                    const file = this.files[0];

                    // Batalkan upload sebelumnya jika masih berjalan
                    if (currentUpload) {
                        currentUpload.abort();
                    }

                    if (file) {
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                        if (!allowedTypes.includes(file.type)) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Tipe file tidak diizinkan. Hanya file PDF dan gambar (JPG, JPEG, PNG) yang diperbolehkan.'
                            });
                            fileInput.val('');
                            return;
                        }

                        if (file.size > 2 * 1024 * 1024) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Ukuran file terlalu besar. Maksimal 2MB.'
                            });
                            fileInput.val('');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('bukti', file);
                        formData.append('id', row.data('id'));

                        Swal.fire({
                            title: 'Uploading...',
                            html: 'Mohon tunggu sedang mengupload file',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Simpan referensi ke upload yang sedang berjalan
                        currentUpload = $.ajax({
                            url: "/program-studi/upload-bukti",
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                currentUpload = null;
                                if (response.success) {
                                    const fileCell = row.find('td').eq(
                                        6); // Kolom bukti akreditasi
                                    row.data('uploaded-filename', response.filename);

                                    // PERBAIKAN 1: Update tampilan di VIEW MODE
                                    fileCell.find('.view-mode').html(
                                        `<a href="${response.file_url}" target="_blank">Lihat Bukti</a>`
                                    );

                                    // PERBAIKAN 2: Update tampilan di EDIT MODE
                                    const editMode = fileCell.find('.edit-mode');

                                    // Hapus link "File saat ini" yang lama
                                    editMode.find('.current-file').remove();
                                    editMode.find('br').remove();
                                    // Tambah link "File saat ini" yang baru (mengarah ke file yang baru diupload)
                                    editMode.prepend(
                                        `<a href="${response.file_url}" target="_blank" class="current-file">File saat ini</a><br/>`
                                    );

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil',
                                        text: 'File berhasil diupload'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                currentUpload = null;
                                if (status !== 'abort') {
                                    fileInput.val('');
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: xhr.responseJSON?.message ||
                                            'Gagal mengupload file'
                                    });
                                }
                            }
                        });
                    }
                });
            }

            // Handler untuk tombol edit
            $(document).on('click', '.btn-edit', function() {
                const row = $(this).closest('tr');
                row.addClass('editing');
                row.find('.view-mode').addClass('d-none');
                row.find('.edit-mode').removeClass('d-none');
            });

            // Handler untuk tombol cancel
            $(document).on('click', '.btn-cancel', function() {
                const row = $(this).closest('tr');

                // Batalkan upload yang sedang berjalan
                if (currentUpload) {
                    currentUpload.abort();
                    currentUpload = null;
                }

                // Bersihkan temp file
                $.ajax({
                    url: '/program-studi/cleanup-temp',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                if (row.data('id') === 'new') {
                    row.remove();
                    renumberRows();
                } else {
                    row.removeClass('editing');
                    row.find('.view-mode').removeClass('d-none');
                    row.find('.edit-mode').addClass('d-none');
                    row.find('input[type="file"]').val(''); // Reset file input
                }
            });

            // Handler untuk menambah baris baru
            $('#btn-add-row').click(function() {
                // Reset baris yang sedang edit
                $('#programStudiTable tbody tr.editing').each(function() {
                    if ($(this).data('id') !== 'new') {
                        $(this).removeClass('editing');
                        $(this).find('.view-mode').removeClass('d-none');
                        $(this).find('.edit-mode').addClass('d-none');
                    }
                });

                // Hapus baris 'new' yang ada
                $('tr[data-id="new"]').remove();

                // Tambah baris baru
                const template = document.querySelector('#new-row-template');
                const clone = template.content.cloneNode(true);
                $('#programStudiTable tbody').append(clone);
                renumberRows();
                initProdiSearch();
            });

            // Handler untuk simpan data baru
            $(document).on('click', '.btn-save-new', function() {
                const row = $(this).closest('tr');
                const formData = new FormData();

                row.find('.editable').each(function() {
                    const field = $(this).data('field');
                    const input = $(this).find('input:visible, select:visible').first();
                    if (input.length && input.attr('type') !== 'file') {
                        formData.append(field, input.val());
                    }
                });

                if (row.data('uploaded-filename')) {
                    formData.append('bukti', row.data('uploaded-filename'));
                }

                $.ajax({
                    url: "/program-studi",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan'
                        });
                    }
                });
            });

            // Handler untuk update data
            // Handler untuk update data - PERBAIKAN
            $('.btn-save').click(function() {
                const row = $(this).closest('tr');
                const id = row.data('id');
                const formData = new FormData();

                formData.append('_method', 'PUT');

                row.find('.editable').each(function() {
                    const field = $(this).data('field');
                    const input = $(this).find('input:visible, select:visible').first();
                    if (input.length && input.attr('type') !== 'file') {
                        formData.append(field, input.val());
                    }
                });

                const uploadedFilename = row.data('uploaded-filename');
                if (uploadedFilename) {
                    formData.append('bukti', uploadedFilename);
                }

                Swal.fire({
                    title: 'Menyimpan...',
                    html: 'Mohon tunggu sedang menyimpan perubahan',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/program-studi/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update data di view-mode
                            row.find('.editable').each(function() {
                                const field = $(this).data('field');
                                const viewMode = $(this).find('.view-mode');
                                const input = $(this).find(
                                    'input:visible, select:visible').first();
                                if (input.length) {
                                    if (field === 'tanggal_kadarluarsa' && input
                                        .val()) {
                                        // Format tanggal untuk tampilan
                                        viewMode.text(input.val());
                                    } else {
                                        viewMode.text(input.val());
                                    }
                                }
                            });

                            // Update file bukti jika ada
                            if (response.data && response.data.bukti) {
                                const fileUrl =
                                    `/storage/bukti_akreditasi/${response.data.bukti}`;
                                const fileCell = row.find('td').eq(6); // Kolom bukti akreditasi

                                fileCell.find('.view-mode').html(
                                    `<a href="${fileUrl}" target="_blank">Lihat Bukti</a>`
                                );

                                const editMode = fileCell.find('.edit-mode');
                                editMode.find('.current-file').remove();
                                editMode.prepend(
                                    `<a href="${fileUrl}" target="_blank" class="current-file">File saat ini</a><br/>`
                                );
                            }

                            // PERBAIKAN: Kembalikan ke mode normal
                            row.removeClass('editing');

                            // Sembunyikan semua edit-mode (termasuk di kolom aksi)
                            row.find('.edit-mode').addClass('d-none');

                            // Tampilkan semua view-mode (termasuk button edit & delete)
                            row.find('.view-mode').removeClass('d-none');

                            // Reset data temporary
                            row.removeData('uploaded-filename');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan'
                        });
                    }
                });
            });

            // Handler untuk hapus data
            $('.btn-delete').click(function() {
                const row = $(this).closest('tr');
                const id = row.data('id');

                Swal.fire({
                    title: 'Apakah anda yakin?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/program-studi/${id}`,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    row.remove();
                                    renumberRows();
                                    Swal.fire(
                                        'Terhapus!',
                                        response.message,
                                        'success'
                                    );
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'Terjadi kesalahan'
                                });
                            }
                        });
                    }
                });
            });

            // Handler sebelum halaman ditutup/refresh
            window.addEventListener('beforeunload', function() {
                if (currentUpload) {
                    currentUpload.abort();
                }

                // Bersihkan temp files
                navigator.sendBeacon('/program-studi/cleanup-temp', new FormData());
            });

            function renumberRows() {
                $('#programStudiTable tbody tr').each(function(index) {
                    $(this).find('td').first().text(index + 1);
                });
            }

            function initProdiSearch() {
                $(document).on('input', '.prodi-search', function() {
                    const searchInput = $(this);
                    const searchResults = searchInput.siblings('.search-results');
                    const searchValue = searchInput.val();
                    const row = searchInput.closest('tr');
                    const fakultasInput = row.find('.fakultas-input');
                    const prodiValue = searchInput.siblings('.prodi-value');

                    if (!searchValue) {
                        searchResults.empty().hide();
                        fakultasInput.val('');
                        prodiValue.val('');
                        return;
                    }

                    $.ajax({
                        url: '/prodi/search',
                        data: {
                            search: searchValue
                        },
                        success: function(data) {
                            searchResults.empty();

                            data.forEach(function(item) {
                                const resultItem = $('<div>')
                                    .addClass('search-item')
                                    .text(item.text)
                                    .on('click', function() {
                                        searchInput.val(item.text);
                                        prodiValue.val(item.text);
                                        if (item.fakultas) {
                                            fakultasInput.val(item.fakultas.text);
                                        }
                                        searchResults.hide();
                                    });
                                searchResults.append(resultItem);
                            });

                            if (data.length > 0) {
                                searchResults.show();
                            } else {
                                searchResults.hide();
                            }
                        }
                    });
                });

                // Sembunyikan hasil pencarian saat klik di luar
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.position-relative').length) {
                        $('.search-results').hide();
                    }
                });
            }

            // Initialize
            initFileUpload();
            initProdiSearch();
        });
    </script>
@endpush
