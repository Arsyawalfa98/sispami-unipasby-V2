@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Kriteria Dokumen</h1>
        <x-create-button route="kriteria-dokumen" title="Kriteria Dokumen" />
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" action="{{ route('kriteria-dokumen.index') }}" class="mb-0">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari..."
                        value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                        <a href="{{ route('kriteria-dokumen.index') }}" class="btn btn-secondary">
                            <i class="fas fa-sync fa-sm"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            @if ($kriteriaDokumen->isEmpty())
                <div class="alert alert-info">
                    Tidak ada data kriteria dokumen yang tersedia.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th width="5%">No</th>
                                <th>Lembaga Akreditasi</th>
                                <th>Periode/Tahun</th>
                                <th>Jenjang</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kriteriaDokumen as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->lembagaAkreditasi->nama }}</td>
                                    <td>{{ $item->periode_atau_tahun ?? $item->lembagaAkreditasi->tahun }}</td>
                                    <td>{{ $item->jenjang->nama }}</td>
                                    <td>
                                        @if (auth()->user()->hasPermission('view-kriteria-dokumen'))
                                            @if (Auth::user()->hasActiveRole('Super Admin') || Auth::user()->hasActiveRole('Admin LPM'))
                                                {{-- Super Admin selalu bisa melihat detail --}}
                                                <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $item->lembaga_akreditasi_id, 'jenjangId' => $item->jenjang_id]) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            @else
                                                {{-- Non-Super Admin cek Jadwal AMI --}}
                                                @if ($activeJadwal && now()->lte($activeJadwal->tanggal_selesai))
                                                    <a href="{{ route('kriteria-dokumen.showGroup', ['lembagaId' => $item->lembaga_akreditasi_id, 'jenjangId' => $item->jenjang_id]) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                @else
                                                    <span class="text-muted">Sudah ditutup</span>
                                                @endif
                                            @endif
                                        @endif

                                        @if (auth()->user()->hasPermission('create-kriteria-dokumen'))
                                            <button type="button" class="btn btn-warning btn-sm copy-btn"
                                                data-toggle="modal" data-target="#copyModal"
                                                data-lembaga-id="{{ $item->lembaga_akreditasi_id }}"
                                                data-jenjang-id="{{ $item->jenjang_id }}"
                                                data-lembaga-nama="{{ $item->lembagaAkreditasi->nama }}"
                                                data-jenjang-nama="{{ $item->jenjang->nama }}"
                                                data-tahun="{{ $item->periode_atau_tahun ?? $item->lembagaAkreditasi->tahun }}">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        @endif

                                        @php
                                            $hasDetails = \App\Models\KriteriaDokumen::where([
                                                'lembaga_akreditasi_id' => $item->lembaga_akreditasi_id,
                                                'jenjang_id' => $item->jenjang_id,
                                            ])
                                                ->whereNotNull('judul_kriteria_dokumen_id')
                                                ->exists();
                                        @endphp

                                        @if (!$hasDetails && auth()->user()->hasPermission('delete-kriteria-dokumen'))
                                            <form
                                                action="{{ route('kriteria-dokumen.destroyGroup', ['lembagaId' => $item->lembaga_akreditasi_id, 'jenjangId' => $item->jenjang_id]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm delete-confirm">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $kriteriaDokumen->links() }}
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="copyModal" tabindex="-1" role="dialog" aria-labelledby="copyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="copyModalLabel">Copy Kriteria Dokumen</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="copyForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Anda akan menyalin kriteria dari:</p>
                        <strong id="sourceKriteria"></strong>
                        <hr>
                        <p>Pilih tujuan penyalinan:</p>
                        <div class="form-group">
                            <label for="dest_lembaga_akreditasi_id">Lembaga Akreditasi Tujuan</label>
                            <select name="dest_lembaga_akreditasi_id" id="dest_lembaga_akreditasi_id" class="form-control" required>
                                <option value="">Pilih Lembaga Akreditasi</option>
                                @foreach($allLembaga as $lembaga)
                                    <option value="{{ $lembaga->id }}">{{ $lembaga->nama }} - {{ $lembaga->tahun }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dest_jenjang_id">Jenjang Tujuan</label>
                            <select name="dest_jenjang_id" id="dest_jenjang_id" class="form-control" required>
                                <option value="">Pilih Jenjang</option>
                                @foreach($allJenjang as $jenjang)
                                    <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Copy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $('.delete-confirm').click(function(e) {
            e.preventDefault();
            const form = $(this).closest('form');

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
                    form.submit();
                }
            });
        });

        $(document).ready(function() {
            $('.copy-btn').click(function() {
                const lembagaId = $(this).data('lembaga-id');
                const jenjangId = $(this).data('jenjang-id');
                const lembagaNama = $(this).data('lembaga-nama');
                const jenjangNama = $(this).data('jenjang-nama');
                const tahun = $(this).data('tahun');

                const sourceText = `${lembagaNama} ${tahun} - ${jenjangNama}`;
                $('#sourceKriteria').text(sourceText);
                
                const actionUrl = `{{ url('kriteria-dokumen') }}/${lembagaId}/${jenjangId}/copy`;
                $('#copyForm').attr('action', actionUrl);
            });
        });
    </script>
@endpush
