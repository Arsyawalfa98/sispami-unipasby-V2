@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Lembaga Akreditasi</h1>
        <a href="{{ route('lembaga-akreditasi.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('lembaga-akreditasi.store') }}">
                @csrf
                <div class="form-group">
                    <label for="nama">Nama Lembaga</label>
                    <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                        name="nama" value="{{ old('nama') }}" required>
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="tahun">Tahun</label>
                    <input type="number" class="form-control @error('tahun') is-invalid @enderror" id="tahun"
                        name="tahun" value="{{ old('tahun') ?? date('Y') }}" min="2000" max="{{ date('Y') + 1 }}"
                        required>
                    @error('tahun')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Program Studi & Fakultas</label>
                    <div id="prodi-container">
                        <div class="row mb-3 prodi-item">
                            <div class="col-md-5">
                                <div class="position-relative">
                                    <input type="text" class="form-control prodi-search" 
                                           placeholder="Ketik untuk mencari Program Studi">
                                    <input type="hidden" name="prodi[]" class="prodi-value">
                                    <div class="dropdown-menu w-100 search-results" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control fakultas-text" readonly>
                                <input type="hidden" name="fakultas[]" class="fakultas-value">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-remove-prodi">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-success" id="btn-add-prodi">
                        <i class="fas fa-plus"></i> Tambah Program Studi
                    </button>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="{{ route('lembaga-akreditasi.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .search-results {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }

        .search-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .search-item:hover {
            background-color: #f8f9fc;
        }

        .position-relative {
            position: relative;
        }
    </style>
@endpush

@push('js')
    <script>
        $(document).ready(function() {
            // Handle tambah prodi
            $('#btn-add-prodi').click(function() {
                const template = $('.prodi-item').first().clone();
                template.find('input').val('');
                template.find('.search-results').empty().hide();
                $('#prodi-container').append(template);
            });

            // Handle hapus prodi
            $(document).on('click', '.btn-remove-prodi', function() {
                if($('.prodi-item').length > 1) {
                    $(this).closest('.prodi-item').remove();
                }
            });

            // Handle pencarian prodi
            $(document).on('input', '.prodi-search', function() {
                const searchInput = $(this);
                const searchValue = searchInput.val();
                const resultsDiv = searchInput.siblings('.search-results');
                const row = searchInput.closest('.prodi-item');
                const prodiInput = row.find('.prodi-value');
                const fakultasText = row.find('.fakultas-text');
                const fakultasValue = row.find('.fakultas-value');

                if (!searchValue) {
                    prodiInput.val('');
                    fakultasText.val('');
                    fakultasValue.val('');
                    resultsDiv.empty().hide();
                    return;
                }

                $.ajax({
                    url: '{{ route('prodi.search') }}',
                    data: { search: searchValue },
                    success: function(data) {
                        resultsDiv.empty();

                        data.forEach(function(item) {
                            const resultItem = $('<div>')
                                .addClass('search-item')
                                .text(item.text)
                                .on('click', function() {
                                    searchInput.val(item.text);
                                    prodiInput.val(item.text);
                                    if (item.fakultas) {
                                        fakultasText.val(item.fakultas.text);
                                        fakultasValue.val(item.fakultas.text);
                                    }
                                    resultsDiv.hide();
                                });
                            resultsDiv.append(resultItem);
                        });

                        if (data.length > 0) {
                            resultsDiv.show();
                        } else {
                            resultsDiv.hide();
                        }
                    }
                });
            });

            // Hide search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('.search-results').hide();
                }
            });
        });
    </script>
@endpush