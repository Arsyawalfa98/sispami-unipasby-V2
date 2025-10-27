<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="bg-primary text-white">
            <tr>
                <th width="3%">No</th>
                <th width="15%">Program Studi</th>
                <th width="12%">Fakultas</th>
                <th width="12%">Standar Akreditasi</th>
                <th width="8%">Periode/Tahun</th>
                <th width="15%">Jadwal AMI</th>
                <th width="15%">Upload Schedule</th>
                <th width="15%">Tim Auditor</th>
                <th width="5%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jadwalAmi as $item)
                <tr>
                    <td>{{ $jadwalAmi->firstItem() + $loop->index }}</td>
                    <td>{{ $item->prodi }}</td>
                    <td>{{ $item->fakultas }}</td>
                    <td>{{ $item->standar_akreditasi }}</td>
                    <td>{{ substr($item->periode, 0, 4) }}</td>
                    <td>
                        <small>
                            <strong>Mulai:</strong><br>
                            {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y H:i') }}<br>
                            <strong>Selesai:</strong><br>
                            {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y H:i') }}
                        </small>
                    </td>
                    
                    {{-- KOLOM UPLOAD SCHEDULE --}}
                    <td>
                        <div class="mb-2">
                            <span class="badge {{ $item->upload_status_badge }}">
                                {{ $item->upload_status }}
                            </span>
                            
                            {{-- Toggle Upload Button untuk Admin LPM/Super Admin dengan permission check --}}
                            @if(Auth::user()->hasPermission('edit-jadwal-ami'))
                                <button class="btn btn-sm {{ $item->upload_enabled ? 'btn-warning' : 'btn-success' }} ml-1" 
                                    onclick="toggleUpload({{ $item->id }}, {{ $item->upload_enabled ? 'false' : 'true' }})"
                                    title="{{ $item->upload_enabled ? 'Nonaktifkan Upload' : 'Aktifkan Upload' }}">
                                    <i class="fas {{ $item->upload_enabled ? 'fa-pause' : 'fa-play' }}"></i>
                                </button>
                            @endif
                        </div>
                        
                        @if($item->upload_enabled && $item->upload_mulai && $item->upload_selesai)
                            <small class="text-muted">
                                <strong>Upload:</strong><br>
                                {{ \Carbon\Carbon::parse($item->upload_mulai)->format('d/m/Y H:i') }} -<br>
                                {{ \Carbon\Carbon::parse($item->upload_selesai)->format('d/m/Y H:i') }}
                            </small>
                            
                            @if($item->hasUploadedFiles())
                                <br><small class="text-info">
                                    <i class="fas fa-file"></i> {{ $item->uploaded_files_count }} file(s)
                                </small>
                            @endif
                        @else
                            <small class="text-muted">Upload tidak diaktifkan</small>
                        @endif
                        
                        @if($item->upload_keterangan)
                            <br><small class="text-secondary" title="{{ $item->upload_keterangan }}">
                                <i class="fas fa-info-circle"></i> {{ Str::limit($item->upload_keterangan, 25) }}
                            </small>
                        @endif
                    </td>
                    
                    <td>
                        {{-- Ketua Auditor --}}
                        @php
                            $ketuaAuditor = $item->timAuditor->where('pivot.role_auditor', 'ketua')->first();
                            $anggotaAuditor = $item->timAuditor->where('pivot.role_auditor', 'anggota');
                        @endphp
                        
                        @if($ketuaAuditor)
                            <div class="mb-1">
                                <span class="badge badge-success mb-1">
                                    <i class="fas fa-crown"></i> {{ $ketuaAuditor->name }}
                                </span>
                                <small class="text-muted">(Ketua)</small>
                            </div>
                        @endif
                        
                        {{-- Anggota Auditor --}}
                        @if($anggotaAuditor->count() > 0)
                            <div class="mb-1">
                                <small class="text-muted"><strong>Anggota:</strong></small>
                                @foreach ($anggotaAuditor as $anggota)
                                    <br><span class="badge badge-info mb-1">
                                        <i class="fas fa-user"></i> {{ $anggota->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            @if(!$ketuaAuditor)
                                <small class="text-muted"><em>Tim auditor belum ditetapkan</em></small>
                            @else
                                <small class="text-muted"><em>Tidak ada anggota</em></small>
                            @endif
                        @endif
                    </td>
                    
                    <td>
                        {{-- Action buttons menggunakan x-action-buttons seperti original --}}
                        <div class="d-flex flex-column">
                            <x-action-buttons route="jadwal-ami" :id="$item->id" permission="jadwal-ami" />
                            
                            {{-- Tambahan Upload Management Button dengan permission check --}}
                            @if($item->upload_enabled && Auth::user()->hasPermission('view-auditor-upload'))
                                @if(Auth::user()->hasActiveRole('Auditor'))
                                    {{-- Auditor hanya lihat yang ditugaskan --}}
                                    @if($item->isUserAssignedAsAuditor(Auth::user()->id))
                                        <a href="{{ route('auditor-upload.showGroup', $item->id) }}" 
                                           class="btn btn-info btn-sm mt-1" title="Upload File">
                                            <i class="fas fa-upload"></i> Upload
                                        </a>
                                    @endif
                                @else
                                    {{-- Role lain yang punya permission bisa lihat semua --}}
                                    <a href="{{ route('auditor-upload.showGroup', $item->id) }}" 
                                       class="btn btn-info btn-sm mt-1" title="Kelola Upload">
                                        <i class="fas fa-upload"></i> Upload
                                    </a>
                                @endif
                            @endif
                            
                            {{-- Upload Stats Button dengan permission check --}}
                            @if($item->upload_enabled && Auth::user()->hasPermission('view-jadwal-ami'))
                                <button class="btn btn-secondary btn-sm mt-1" 
                                    onclick="showUploadStats({{ $item->id }})" title="Statistik Upload">
                                    <i class="fas fa-chart-bar"></i> Stats
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end">
    {{ $jadwalAmi->links() }}
</div>
