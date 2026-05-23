@extends('layouts.app')

@section('title', 'Certificates')
@section('page-title', 'Certificates')
@section('page-sub', 'Issue and manage certificates')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search certificate or person" style="min-width:280px;">
        <select name="per_page" class="form-select" style="width:auto;">
            @foreach([25,50,100] as $size)
                <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>{{ $size }} / page</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
    </form>

    <a href="{{ route('certificates.create') }}" class="btn btn-primary">
        <i class="bi bi-patch-check-fill me-1"></i>Issue Certificate
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-header-title"><i class="bi bi-patch-check-fill"></i> All Certificates</div>
    </div>
    <div class="tbl-wrap">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Certificate No.</th>
                    <th>Person</th>
                    <th>Title</th>
                    <th>Issued</th>
                    <th>Verify</th>
                    <th>PDF</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $certificate)
                    <tr>
                        <td class="fw-semibold">{{ $certificate->certificate_number }}</td>
                        <td>
                            <a href="{{ route('persons.show', $certificate->person) }}" class="text-decoration-none fw-semibold">
                                {{ $certificate->person->full_name }}
                            </a>
                        </td>
                        <td>{{ $certificate->title ?: '—' }}</td>
                        <td>{{ $certificate->issued_at?->format('M d, Y') ?: '—' }}</td>
                        <td>
                            <a href="{{ $certificate->verify_url }}" target="_blank" class="btn btn-sm btn-outline-secondary">Open</a>
                        </td>
                        <td>
                            @if($certificate->pdfMedia)
                                <a href="{{ route('media.download', $certificate->pdfMedia) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                <a href="{{ route('certificates.qr', $certificate) }}" class="btn btn-sm btn-outline-secondary">QR</a>
                                <a href="{{ route('certificates.edit', $certificate) }}" class="btn btn-sm btn-primary">Edit</a>
                                @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('certificates.destroy', $certificate) }}" onsubmit="return confirm('Delete this certificate?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No certificates found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $certificates->links() }}</div>
@endsection
