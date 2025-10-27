@extends('layouts.admin')
@php
    use App\Models\PenilaianKriteria;
@endphp
@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Penilaian Kriteria {{ $kriteriaDokumen->judulKriteriaDokumen?->nama_kriteria_dokumen ?? '' }}
            <br>
            <small class="text-muted">{{ $selectedProdi }}</small>
        </h1>
        <a href="{{ route('pemenuhan-dokumen.showGroup', [
            'lembagaId' => $kriteriaDokumen->lembaga_akreditasi_id,
            'jenjangId' => $kriteriaDokumen->jenjang_id,
            'prodi' => $selectedProdi,
        ]) }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

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
        
        // Super Admin dan Admin LPM selalu bisa mengubah nilai tanpa batasan jadwal
        $isSuperAdmin = Auth::user()->hasActiveRole('Super Admin');
        $isAdminLPM = Auth::user()->hasActiveRole('Admin LPM');
        $isAuditor = Auth::user()->hasActiveRole('Auditor');
        $isAdmin = Auth::user()->hasActiveRole('Admin Prodi');
        
        $allowEdit = $isSuperAdmin || $isAdminLPM || !$jadwalExpired;
        
        // Cek status penilaian
        $statusDiajukan = ($penilaian->status == PenilaianKriteria::STATUS_DIAJUKAN);
        $statusRevisi = ($penilaian->status == PenilaianKriteria::STATUS_REVISI);
        $statusDisetujui = ($penilaian->status == PenilaianKriteria::STATUS_DISETUJUI);
        $statusPenilaian = ($penilaian->status == PenilaianKriteria::STATUS_PENILAIAN);
    @endphp

    @if ($jadwalExpired && !$isSuperAdmin && !$isAdminLPM)
        <div class="alert alert-warning">
            <i class="fas fa-clock"></i> Jadwal AMI untuk periode ini telah berakhir pada 
            {{ $jadwalAmi ? \Carbon\Carbon::parse($jadwalAmi->tanggal_selesai)->format('d M Y H:i') : 'tanggal tidak diketahui' }}. 
            Anda hanya dapat melihat data yang ada.
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Data Kriteria</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Kriteria:</strong> {{ $kriteriaDokumen->judulKriteriaDokumen?->nama_kriteria_dokumen ?? '' }}
                    </p>
                    <p><strong>Element:</strong> {{ $kriteriaDokumen->element }}</p>
                    <p><strong>Indikator:</strong> {{ $kriteriaDokumen->indikator }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Lembaga Akreditasi:</strong> {{ $kriteriaDokumen->lembagaAkreditasi->nama }}</p>
                    <p><strong>Jenjang:</strong> {{ $kriteriaDokumen->jenjang->nama }}</p>
                    <p><strong>Periode/Tahun:</strong> {{ $kriteriaDokumen->periode_atau_tahun }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary">
            <h6 class="m-0 font-weight-bold text-white">Informasi</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p style="white-space: pre-wrap;"><strong>ISI INFORMASI :</strong><br>{{ $informasi }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-white">Form Penilaian</h6>
            @if ($penilaian->status)
                <span class="badge badge-light">Status: {{ $penilaian->status }}</span>
            @endif
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ $penilaian->id ? route('penilaian-kriteria.update', $penilaian->id) : route('penilaian-kriteria.store', $kriteriaDokumen->id) }}">
                @csrf
                @if ($penilaian->id)
                    @method('PUT')
                @endif
                <input type="hidden" name="prodi" value="{{ $selectedProdi }}">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Capaian (Nilai : 0 - 4)</label>
                            @php
                                // Tentukan apakah input bisa diedit
                                $inputReadonly = !($allowEdit && !$statusDisetujui) && !$isSuperAdmin || !$isAuditor;
                            @endphp
                            <input type="number" name="nilai" class="form-control"
                                value="{{ old('nilai', $penilaian->nilai) }}" step="0.01" min="0" max="4"
                                required {{ $inputReadonly ? 'readonly' : '' }}>
                            <small class="text-muted">Nilai antara 0 sampai 4 
                                @if ($inputReadonly && !$isAuditor)
                                    (hanya Auditor yang dapat mengubah)
                                @elseif ($inputReadonly && $statusDisetujui)
                                    (tidak dapat diubah karena status sudah disetujui)
                                @elseif ($inputReadonly)
                                    (tidak dapat diubah karena jadwal telah berakhir)
                                @endif
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bobot</label>
                            <!-- Ambil bobot dari Kriteriadokumen -->
                            @php
                                $kebutuhanDokumen = App\Models\Kriteriadokumen::where(
                                    'id',
                                    $kriteriaDokumen->id,
                                )->first();
                                $bobot = $kebutuhanDokumen ? $kebutuhanDokumen->bobot : 0;
                            @endphp
                            <input type="number" name="bobot" class="form-control"
                                value="{{ old('bobot', $bobot ?? $penilaian->bobot) }}" readonly>
                            <small class="text-muted">Bobot diambil dari pengaturan Kriteria Dokumen Yang Telah di
                                Setting</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tertimbang</label>
                            <input type="text" id="tertimbang" class="form-control" value="{{ $penilaian->tertimbang }}"
                                readonly>
                            {{-- <small class="text-muted">Nilai × Bobot (otomatis)</small> --}}
                        </div>
                    </div>

                    <div class="col-md-4" hidden >
                        <div class="form-group">
                            <label>Nilai Auditor (0-4)</label>
                            @php
                                // Tentukan apakah input nilai auditor bisa diedit 
                                // (Auditor dapat edit jika status diajukan dan bukan disetujui, Super Admin dan Admin LPM selalu bisa)
                                //$auditorCanEdit = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                                $auditorCanEdit = $isSuperAdmin; 
                                $auditorInputReadonly = !$auditorCanEdit;
                            @endphp
                            <input type="number" name="nilai_auditor" class="form-control"
                                value="{{ old('nilai_auditor', $penilaian->nilai_auditor) }}" step="0.01" min="0"
                                max="4" {{ $auditorInputReadonly ? 'readonly' : '' }}>
                            <small class="text-muted">Nilai dari auditor 
                                @if ($auditorInputReadonly && !$isAuditor)
                                    (hanya Auditor yang dapat mengubah)
                                @elseif ($auditorInputReadonly && $isAuditor && $statusDisetujui)
                                    (tidak dapat diubah karena status sudah disetujui)
                                @elseif ($auditorInputReadonly && $isAuditor && !$statusDiajukan)
                                    (tidak dapat diubah karena status belum diajukan)
                                @elseif ($auditorInputReadonly)
                                    (tidak dapat diubah karena jadwal telah berakhir)
                                @endif
                            </small>
                        </div>
                        
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tanggal / Waktu Pemenuhan</label>
                            @php
                                // Tentukan apakah input nilai auditor bisa diedit 
                                // (Auditor dapat edit jika status diajukan dan bukan disetujui, Super Admin dan Admin LPM selalu bisa)
                                //$auditorCanEdit = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                                $auditorCanEdit = $isSuperAdmin || $isAuditor; 
                                $auditorInputReadonly = !$auditorCanEdit;
                            @endphp
                            <input type="datetime-local" name="tanggal_pemenuhan" class="form-control"
                                step="1" value="{{ $penilaian->tanggal_pemenuhan ? date('Y-m-d\TH:i:s', strtotime($penilaian->tanggal_pemenuhan)) : '' }}" {{ $auditorInputReadonly ? 'readonly' : '' }}>
                        </div>
                    </div>

                    <div class="col-md-4">
                         <div class="form-group">
                            <label for="penanggung_jawab">Penanggung Jawab:</label>
                            <input type="text" id="penanggung_jawab" name="penanggung_jawab" class="form-control" value="{{ $namaKaprodi }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status_temuan">Status Temuan:</label>
                            @php
                                // Tentukan apakah input nilai auditor bisa diedit 
                                // (Auditor dapat edit jika status diajukan dan bukan disetujui, Super Admin dan Admin LPM selalu bisa)
                                //$auditorCanEdit = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                                $auditorCanEdit = $isSuperAdmin || $isAuditor; 
                                $auditorInputReadonly = !$auditorCanEdit;
                            @endphp
                            <select name="status_temuan" id="status_temuan" class="form-control" {{ $auditorInputReadonly ? 'readonly' : '' }}>
                                <option value="" {{ $penilaian->status_temuan == '' ? 'selected' : '' }}>Semua</option>
                                <option value="OBSERVASI" {{ $penilaian->status_temuan == 'OBSERVASI' ? 'selected' : '' }}>OBSERVASI (OB)</option>
                                <option value="KETIDAKSESUAIAN" {{ $penilaian->status_temuan == 'KETIDAKSESUAIAN' ? 'selected' : '' }}>KETIDAKSESUAIAN (KTS)</option>
                                <option value="TERCAPAI" {{ $penilaian->status_temuan == 'TERCAPAI' ? 'selected' : '' }}>TERCAPAI</option>
                            </select>
                        </div>
                    </div>
                    
                </div>

                <div class="form-group">
                    <label>Rekomendasi</label>
                    @php
                        // Auditor can edit revisi if status is diajukan and not disetujui
                        $canEditRevisi = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                    @endphp
                    <textarea name="revisi" class="form-control" rows="3" {{ (!$canEditRevisi) ? 'readonly' : '' }}>{{ old('revisi', $penilaian->revisi) }}</textarea>
                    <small class="text-muted">
                        @if (!$allowEdit)
                            (tidak dapat diubah karena jadwal telah berakhir)
                        @elseif ($statusDisetujui)
                            (tidak dapat diubah karena status sudah disetujui)
                        @elseif ($isAuditor && !$statusDiajukan && !$isSuperAdmin && !$isAdminLPM)
                            (tidak dapat diubah karena status belum diajukan)
                        @endif
                    </small>
                </div>

                <div class="form-group">
                    <label>Hasil AMI</label>
                    @php
                        // Auditor can edit revisi if status is diajukan and not disetujui
                        $canEditRevisi = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                    @endphp
                    <textarea name="hasil_ami" class="form-control" rows="3" {{ (!$canEditRevisi) ? 'readonly' : '' }}>{{ old('hasil_ami', $penilaian->hasil_ami) }}</textarea>
                    <small class="text-muted"> 
                        @if (!$allowEdit)
                            (tidak dapat diubah karena jadwal telah berakhir)
                        @elseif ($statusDisetujui)
                            (tidak dapat diubah karena status sudah disetujui)
                        @elseif ($isAuditor && !$statusDiajukan && !$isSuperAdmin && !$isAdminLPM)
                            (tidak dapat diubah karena status belum diajukan)
                        @endif
                    </small>
                </div>

                <div class="form-group">
                    <label>Output</label>
                    @php
                        // Auditor can edit revisi if status is diajukan and not disetujui
                        $canEditRevisi = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                    @endphp
                    <textarea name="output" class="form-control" rows="3" {{ (!$canEditRevisi) ? 'readonly' : '' }}>{{ old('output', $penilaian->output) }}</textarea>
                    <small class="text-muted"> 
                        @if (!$allowEdit)
                            (tidak dapat diubah karena jadwal telah berakhir)
                        @elseif ($statusDisetujui)
                            (tidak dapat diubah karena status sudah disetujui)
                        @elseif ($isAuditor && !$statusDiajukan && !$isSuperAdmin && !$isAdminLPM)
                            (tidak dapat diubah karena status belum diajukan)
                        @endif
                    </small>
                </div>

                <div class="form-group">
                    <label>Akar Penyebab Masalah</label>
                    @php
                        // Auditor can edit revisi if status is diajukan and not disetujui
                        $canEditRevisi = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                    @endphp
                    <textarea name="akar_penyebab_masalah" class="form-control" rows="3" {{ (!$canEditRevisi) ? 'readonly' : '' }}>{{ old('akar_penyebab_masalah', $penilaian->akar_penyebab_masalah) }}</textarea>
                    <small class="text-muted"> 
                        @if (!$allowEdit)
                            (tidak dapat diubah karena jadwal telah berakhir)
                        @elseif ($statusDisetujui)
                            (tidak dapat diubah karena status sudah disetujui)
                        @elseif ($isAuditor && !$statusDiajukan && !$isSuperAdmin && !$isAdminLPM)
                            (tidak dapat diubah karena status belum diajukan)
                        @endif
                    </small>
                </div>

                <div class="form-group">
                    <label>Tinjauan Efektivitas Koreksi</label>
                    @php
                        // Auditor can edit revisi if status is diajukan and not disetujui
                        $canEditRevisi = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                    @endphp
                    <textarea name="tinjauan_efektivitas_koreksi" class="form-control" rows="3" {{ (!$canEditRevisi) ? 'readonly' : '' }}>{{ old('tinjauan_efektivitas_koreksi', $penilaian->tinjauan_efektivitas_koreksi) }}</textarea>
                    <small class="text-muted"> 
                        @if (!$allowEdit)
                            (tidak dapat diubah karena jadwal telah berakhir)
                        @elseif ($statusDisetujui)
                            (tidak dapat diubah karena status sudah disetujui)
                        @elseif ($isAuditor && !$statusDiajukan && !$isSuperAdmin && !$isAdminLPM)
                            (tidak dapat diubah karena status belum diajukan)
                        @endif
                    </small>
                </div>

                <div class="form-group">
                    <label>Kesimpulan</label>
                    @php
                        // Auditor can edit revisi if status is diajukan and not disetujui
                        $canEditRevisi = ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM;
                    @endphp
                    <textarea name="kesimpulan" class="form-control" rows="3" {{ (!$canEditRevisi) ? 'readonly' : '' }}>{{ old('kesimpulan', $penilaian->kesimpulan) }}</textarea>
                    <small class="text-muted"> 
                        @if (!$allowEdit)
                            (tidak dapat diubah karena jadwal telah berakhir)
                        @elseif ($statusDisetujui)
                            (tidak dapat diubah karena status sudah disetujui)
                        @elseif ($isAuditor && !$statusDiajukan && !$isSuperAdmin && !$isAdminLPM)
                            (tidak dapat diubah karena status belum diajukan)
                        @endif
                    </small>
                </div>
                {{-- //konfirmasi revisi tidak di gunakan --}}
                {{-- @if (!empty($penilaian->revisi) && (($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) || $isSuperAdmin || $isAdminLPM))
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="revisi_confirmed"
                                name="revisi_confirmed" value="1" {{ $penilaian->revisi_confirmed ? 'checked' : '' }}>
                            <label class="custom-control-label" for="revisi_confirmed">
                                <strong>Konfirmasi Revisi Selesai</strong>
                            </label>
                            <div class="text-muted small">Centang jika revisi sudah selesai dikerjakan</div>
                        </div>
                    </div>
                @endif --}}
                
                @php
                    // Tentukan apakah tombol simpan ditampilkan:
                    // 1. Super Admin dan Admin LPM: selalu tampil kecuali status DISETUJUI
                    // 2. Auditor: hanya tampil jika status DIAJUKAN dan bukan DISETUJUI
                    // 3. Admin biasa: tampil jika status PENILAIAN atau REVISI dan bukan DISETUJUI
                    
                    $showSaveButton = ($isSuperAdmin || $isAdminLPM) && !$statusDisetujui || 
                     ($isAuditor && $statusDiajukan && !$statusDisetujui && $allowEdit) ||
                     ($isAdmin && ($statusPenilaian || $statusRevisi) && !$statusDisetujui && $allowEdit);
                @endphp
                
                @if ($showSaveButton)
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Penilaian
                    </button>
                @else
                    <div class="alert alert-secondary">
                        @if (!$allowEdit)
                            <i class="fas fa-lock"></i> Penilaian tidak dapat diubah karena jadwal AMI telah berakhir.
                        @elseif ($statusDisetujui)
                            <i class="fas fa-check-circle"></i> Penilaian telah disetujui dan tidak dapat diubah.
                        @elseif ($isAuditor && !$statusDiajukan)
                            <i class="fas fa-info-circle"></i> Auditor hanya dapat mengubah penilaian dengan status diajukan.
                        @elseif ($isAdmin && !$statusRevisi)
                            <i class="fas fa-info-circle" hidden></i> Admin hanya dapat mengubah penilaian dengan status revisi.
                        @else
                            <i class="fas fa-lock"></i> Anda tidak memiliki hak akses untuk mengubah penilaian.
                        @endif
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // Fungsi untuk menghitung tertimbang
            function updateTertimbang() {
                const nilai = parseFloat($('input[name="nilai"]').val()) || 0;
                const bobot = parseFloat($('input[name="bobot"]').val()) || 0;
                const tertimbang = nilai * bobot;
                $('#tertimbang').val(tertimbang.toFixed(2));
            }

            // Panggil fungsi saat nilai atau bobot berubah
            $('input[name="nilai"], input[name="bobot"]').on('input', function() {
                updateTertimbang();
            });

            // Panggil fungsi saat halaman dimuat
            updateTertimbang();
        });
    </script>
@endpush