@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Kelola Kebutuhan {{ $kriteriaDokumen->judulKriteriaDokumen?->nama_kriteria_dokumen ?? 'Kriteria' }}
            Pemenuhan Dokumen {{ $kriteriaDokumen->lembagaAkreditasi->nama ?? '' }}
            {{ $kriteriaDokumen->jenjang->nama ?? '' }}
            {{ $kriteriaDokumen->periode_atau_tahun ?? '' }}
            @if ($selectedProdi)
                <br>
                <small class="text-muted">Program Studi: {{ $selectedProdi }}</small>
            @endif
        </h1>
        <div class="">
            @php
                // Cek jadwal AMI untuk prodi yang dipilih
                $jadwalAmi = \App\Models\JadwalAmi::where('prodi', 'like', "%{$selectedProdi}%")
                    ->whereRaw("LEFT(periode, 4) = ?", [$kriteriaDokumen->periode_atau_tahun ?? date('Y')])
                    ->first();
                    
                $jadwalActive = false;
                $jadwalExpired = false;
                
                if ($jadwalAmi) {
                    $jadwalMulai = \Carbon\Carbon::parse($jadwalAmi->tanggal_mulai);
                    $jadwalSelesai = \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai);
                    $now = \Carbon\Carbon::now();
                    
                    $jadwalActive = $now->between($jadwalMulai, $jadwalSelesai);
                    $jadwalExpired = $now->greaterThan($jadwalSelesai);
                }
                
                // Super Admin selalu bisa menambah dokumen tanpa batasan jadwal
                $allowAddDocument = Auth::user()->hasActiveRole('Super Admin') || !$jadwalExpired;
            @endphp
            
            @if ($jadwalExpired && !Auth::user()->hasActiveRole('Super Admin'))
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i> Jadwal AMI untuk periode ini telah berakhir pada 
                    {{ $jadwalAmi ? \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai)->format('d M Y H:i') : 'tanggal tidak diketahui' }}. 
                    Anda hanya dapat melihat data yang ada.
                </div>
            @endif
            
            @if ($allowAddDocument && count($pemenuhanDokumen) < $kriteriaDokumen->kebutuhan_dokumen)
                <x-create-button route="dokumen-persyaratan-pemenuhan-dokumen" title="Dokumen" :params="['kriteriaDokumenId' => $kriteriaDokumen->id, 'prodi' => $selectedProdi]" />
            @endif
            <a href="{{ route('pemenuhan-dokumen.showGroup', [
                'lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id,
                'jenjangId' => $kriteriaDokumen->jenjang_id,
                'prodi' => $selectedProdi,
            ]) }}"
                class="btn btn-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Daftar Dokumen - {{ $selectedProdi }}</h6>
        </div>
        <div class="card-body">
            @if ($pemenuhanDokumen->isEmpty())
                <div class="alert alert-info">
                    Belum ada dokumen yang diupload untuk Program Studi ini.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Dokumen</th>
                                <th width="15%">File</th>
                                <th>Tambahan Informasi</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pemenuhanDokumen as $dokumen)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $dokumen->nama_dokumen }}</td>
                                    <td class="text-center">
                                        @if ($dokumen->file)
                                            <a href="{{ Storage::url('pemenuhan_dokumen/' . $dokumen->file) }}"
                                                class="btn btn-sm btn-warning" target="_blank">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        @else
                                            <span class="badge badge-danger">No File</span>
                                        @endif
                                    </td>
                                    <td>{{ $dokumen->tambahan_informasi }}</td>
                                    <td>
                                        @if ($allowAddDocument)
                                            <x-action-buttons route="dokumen-persyaratan-pemenuhan-dokumen" :id="$dokumen->id"
                                                :params="['prodi' => $selectedProdi]" />
                                        @else
                                            <!-- Tampilkan hanya tombol view ketika jadwal sudah lewat -->
                                            <a href="{{ route('dokumen-persyaratan-pemenuhan-dokumen.show', $dokumen->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // Handle delete button dari x-action-buttons
            $(document).on('click', '.delete-action', function(e) {
                e.preventDefault();
                const deleteUrl = $(this).attr('href');
                const itemName = $(this).data('item-name');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: itemName,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Terhapus!',
                                        'Dokumen berhasil dihapus.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'Terjadi kesalahan saat menghapus dokumen.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush