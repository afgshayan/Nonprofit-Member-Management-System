@extends('layouts.app')

@section('title', 'Categories')
@section('page-title', 'Category Management')
@section('page-sub', 'Create categories before assigning or importing members')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-tag-fill me-2"></i>Add Category</div>
            <div class="card-body">
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <label for="name" class="form-label fw-semibold">Category Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" maxlength="100" required class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Volunteers">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button class="btn btn-primary w-100 mt-3"><i class="bi bi-plus-circle me-1"></i>Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Available Categories <span class="text-muted fw-normal ms-1">({{ $categories->count() }})</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-4">Name</th><th>Members</th><th class="text-end pe-4">Actions</th></tr></thead>
                        <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $category->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $category->persons_count }}</span></td>
                                <td class="text-end pe-4">
                                    <form method="POST" action="{{ route('categories.update', $category) }}" class="d-inline-flex gap-1">
                                        @csrf @method('PUT')
                                        <input name="name" value="{{ $category->name }}" maxlength="100" required class="form-control form-control-sm" style="width:180px">
                                        <button class="btn btn-sm btn-outline-primary" title="Save"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Delete this category? It will be removed from all members.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-5"><i class="bi bi-tags fs-2 d-block mb-2"></i>No categories yet. Add one to begin.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection