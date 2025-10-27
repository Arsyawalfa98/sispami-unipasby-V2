{{-- resources/views/pemenuhan-dokumen/tim-auditor-display.blade.php --}}
@php
    // Parse tim auditor dari detail yang sudah disiapkan oleh JadwalService
    if (isset($timAuditorDetail) && is_array($timAuditorDetail)) {
        $auditorInfo = $timAuditorDetail;
    } else {
        // Fallback parsing dari string
        $auditorInfo = ['ketua' => null, 'anggota' => []];
        
        if (!empty($timAuditorString) && $timAuditorString !== '-') {
            // Try to parse if the string contains role information
            if (strpos($timAuditorString, '(Ketua)') !== false || strpos($timAuditorString, '(Anggota)') !== false) {
                $ketua = null;
                $anggota = [];
                
                $auditors = array_map('trim', explode(',', $timAuditorString));
                
                foreach ($auditors as $auditor) {
                    if (strpos($auditor, '(Ketua)') !== false) {
                        $ketua = str_replace(' (Ketua)', '', $auditor);
                    } elseif (strpos($auditor, '(Anggota)') !== false) {
                        $anggota[] = str_replace(' (Anggota)', '', $auditor);
                    } else {
                        // Fallback: if no role specified, assume first is ketua
                        if ($ketua === null) {
                            $ketua = $auditor;
                        } else {
                            $anggota[] = $auditor;
                        }
                    }
                }
                
                $auditorInfo = ['ketua' => $ketua, 'anggota' => $anggota];
            } else {
                // Simple fallback: first auditor is ketua, rest are anggota
                $auditors = array_map('trim', explode(',', $timAuditorString));
                $ketua = !empty($auditors) ? array_shift($auditors) : null;
                $anggota = $auditors;
                
                $auditorInfo = ['ketua' => $ketua, 'anggota' => $anggota];
            }
        }
    }
@endphp

@if ($auditorInfo['ketua'] || !empty($auditorInfo['anggota']))
    <div class="tim-auditor-container">
        {{-- Ketua Auditor --}}
        @if ($auditorInfo['ketua'])
            <div class="mb-1">
                <span class="badge badge-success auditor-badge">
                    <i class="fas fa-crown"></i> {{ $auditorInfo['ketua'] }} (Ketua)
                </span>
            </div>
        @endif

        {{-- Anggota Auditor --}}
        @if (!empty($auditorInfo['anggota']))
            <div class="mb-1">
                @foreach ($auditorInfo['anggota'] as $anggota)
                    <span class="badge badge-info auditor-badge">
                        <i class="fas fa-user"></i> {{ trim($anggota) }} (Anggota)
                    </span>
                    @if (!$loop->last)<br>@endif
                @endforeach
            </div>
        @elseif ($auditorInfo['ketua'])
            <div class="mb-1">
                <small class="text-muted"><em>Tidak ada anggota</em></small>
            </div>
        @endif
    </div>
@else
    <span class="badge badge-secondary">{{ $timAuditorString ?: 'Tidak ada auditor' }}</span>
@endif

<style>
.auditor-badge {
    font-size: 0.75em;
    margin-bottom: 2px;
    display: inline-block;
}

.badge-success {
    background-color: #28a745 !important;
}

.badge-info {
    background-color: #17a2b8 !important;
}

.tim-auditor-container {
    line-height: 1.4;
}

.fas.fa-crown {
    color: #ffc107;
    margin-right: 2px;
}

.fas.fa-user {
    margin-right: 2px;
}
</style>