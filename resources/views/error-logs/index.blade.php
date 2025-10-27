@extends('layouts.admin')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Error Logs</h1>
        <div>
            <button class="btn btn-danger btn-sm" onclick="confirmClear()">
                <i class="fas fa-trash"></i> Clear Logs
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Datetime</th>
                            <th>Error Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td width="200">{{ $log['datetime'] }}</td>
                                <td>
                                    <pre style="margin: 0; white-space: pre-wrap;">{{ Str::limit($log['message'], 150) }}</pre>
                                </td>
                                <td width="100">
                                    <button class="btn btn-sm btn-info" 
                                            onclick='showDetails(@json($log['message']))'>
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No error logs found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for log details -->
    <div class="modal fade" id="logDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error Log Detail</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <pre id="logDetails" class="bg-light p-3" style="white-space: pre-wrap; word-break: break-word; max-height: 500px; overflow-y: auto;"></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    function showDetails(message) {
        document.getElementById('logDetails').textContent = message;
        $('#logDetailModal').modal('show');
    }

    function confirmClear() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete all error logs. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, clear it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '{{ route("error-logs.clear") }}';
            }
        });
    }
</script>
@endpush