@extends('layouts.app')

@section('title', 'Edit Certificate')
@section('page-title', 'Edit Certificate')
@section('page-sub', $certificate->certificate_number)

@section('content')
<div class="card">
    <div class="card-header">
        <div class="card-header-title"><i class="bi bi-patch-check-fill"></i> Edit Certificate</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('certificates.update', $certificate) }}">
            @method('PUT')
            @include('certificates._form')
            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
