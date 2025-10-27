@extends('layouts.admin')

@section('main-content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Detail Dokumen SPMI & AMI</h1>
            <a href="{{ route('dokumen-spmi-ami.index') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Informasi Dokumen</h5>
                        <hr>
                        <div class="mb-3">
                            <strong>Kategori Dokumen:</strong>
                            <p>{{ $dokumenSPMIAMI->kategori_dokumen }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Nama Dokumen:</strong>
                            <p>{{ $dokumenSPMIAMI->nama_dokumen }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <p>
                                @if($dokumenSPMIAMI->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>File Dokumen</h5>
                        <hr>
                        <div class="mb-3">
                            <strong>File:</strong>
                            <p>
                                <a href="{{ Storage::url($dokumenSPMIAMI->file_path) }}" 
                                   class="btn btn-sm btn-warning"
                                   target="_blank">
                                    <i class="fas fa-download"></i> Download File
                                </a>
                            </p>
                        </div>
                        <div class="mb-3">
                            <strong>Nama File:</strong>
                            <p>{{ basename($dokumenSPMIAMI->file_path) }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Terakhir Diperbarui:</strong>
                            <p>{{ $dokumenSPMIAMI->updated_at->format('d F Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('edit-dokspmiami'))
                    <div class="mt-3">
                        <a href="{{ route('dokumen-spmi-ami.edit', $dokumenSPMIAMI->id) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Dokumen
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .badge {
        font-size: 90%;
    }
    .btn-warning {
        color: #fff;
    }
    .btn-warning:hover {
        color: #fff;
    }
</style>
@endpush