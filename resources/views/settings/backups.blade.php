@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-1"><i class="bi bi-cloud-arrow-down"></i> Backup & Restore</h1>
            <p class="text-muted small">Create backups of your database and media files for migration or recovery</p>
        </div>
    </div>

    <!-- Cache Clear Section -->
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10">
            <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Clear Cache</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Clear application cache to free memory and remove stale data.</p>
            <button id="clearCacheBtn" class="btn btn-warning btn-sm">
                <i class="bi bi-trash"></i> Clear Cache Now
            </button>
        </div>
    </div>

    <!-- Create Backup Section -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary bg-opacity-10">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create Backup</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Includes: database, settings, and all media files</p>
            <form id="backupForm" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small text-uppercase fw-bold">Notes (optional)</label>
                    <input type="text" id="backupNotes" class="form-control form-control-sm" 
                           placeholder="e.g., Before migration to new domain">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-cloud-arrow-up"></i> Create Backup
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Backups List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-archive"></i> Backups ({{ count($backups) }})</h5>
        </div>
        <div class="card-body p-0">
            @if($backups->isEmpty())
                <div class="alert alert-info m-3 mb-0">
                    <i class="bi bi-info-circle"></i> No backups yet. Create one above.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Size</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                                <tr>
                                    <td class="align-middle">
                                        <small class="text-muted">{{ $backup->created_at->format('M d, Y H:i') }}</small>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-light text-dark">{{ $backup->human_size }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <small class="text-muted">{{ $backup->notes ?: '—' }}</small>
                                    </td>
                                    <td class="align-middle">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('backups.download', $backup->id) }}" 
                                               class="btn btn-outline-primary" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-success restore-btn" 
                                                    data-id="{{ $backup->id }}" title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-btn" 
                                                    data-id="{{ $backup->id }}" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.getElementById('backupForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const notes = document.getElementById('backupNotes').value;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating...';
    
    try {
        const response = await fetch('{{ route("backups.create") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notes })
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-up"></i> Create Backup';
    }
});

document.getElementById('clearCacheBtn').addEventListener('click', async () => {
    if (!confirm('Clear application cache?')) return;
    
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Clearing...';
    
    try {
        const response = await fetch('{{ route("cache.clear") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash"></i> Clear Cache Now';
    }
});

document.querySelectorAll('.restore-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('Restore this backup? Current data will be replaced. You will be logged out.')) return;
        
        const id = btn.dataset.id;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        try {
            const response = await fetch(`{{ url('backups') }}/${id}/restore`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                window.location.href = '{{ route("login") }}';
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
        }
    });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('Delete this backup? This cannot be undone.')) return;
        
        const id = btn.dataset.id;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        try {
            const response = await fetch(`{{ url('backups') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();
            
            if (data.success) {
                btn.closest('tr').remove();
                alert(data.message);
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash"></i>';
        }
    });
});
</script>

<style>
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
</style>
@endsection
