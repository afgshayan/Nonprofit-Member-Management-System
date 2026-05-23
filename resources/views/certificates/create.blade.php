@extends('layouts.app')

@section('title', 'Issue Certificate')
@section('page-title', 'Issue Certificate')
@section('page-sub', 'Create a certificate for an existing person')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="card-header-title"><i class="bi bi-patch-check-fill"></i> Issue Certificate</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('certificates.store') }}">
            @include('certificates._form')
            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Issue Certificate</button>
                <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
