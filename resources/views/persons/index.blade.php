@extends('layouts.app')

@section('title', 'All Members')
@section('page-title', 'Members')
@section('page-sub', 'Browse, search, sort and manage all members')
@section('breadcrumb')
    <li class="breadcrumb-item active">All Members</li>
@endsection

@section('content')

{{-- ── Stat row ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="stat-card c-blue">
            <div class="stat-label">
                Total Members
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-val">{{ number_format($totalCount) }}</div>
            <div class="stat-sub">All registered members</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card c-green">
            <div class="stat-label">
                Filtered Results
                <i class="bi bi-funnel-fill"></i>
            </div>
            <div class="stat-val">{{ number_format($persons->total()) }}</div>
            <div class="stat-sub">Matching current filters</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card c-orange">
            <div class="stat-label">
                Countries
                <i class="bi bi-globe"></i>
            </div>
            <div class="stat-val">{{ $countries->count() }}</div>
            <div class="stat-sub">Unique countries</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card c-purple">
            <div class="stat-label">
                Cities
                <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div class="stat-val">{{ $cities->count() }}</div>
            <div class="stat-sub">Unique cities</div>
        </div>
    </div>
</div>

{{-- ── Filter card ── --}}
<div class="filter-card mb-4">
    <form method="GET" action="{{ route('persons.index') }}" id="filterForm">
            <input type="hidden" name="sort"      value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="hidden" name="per_page"  value="{{ $perPage }}">

            <div class="row g-2 align-items-end">
                <!--  Search -->
                <div class="col-12 col-lg-4">
                    <label class="form-label small fw-600 mb-1" style="font-weight:600; font-size:.78rem; color:#374151;">
                        <i class="bi bi-search me-1 text-muted"></i>Search
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Name, email, phone …"
                               value="{{ $search }}" maxlength="200">
                    </div>
                </div>

                <!--  City -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-600 mb-1" style="font-weight:600; font-size:.78rem; color:#374151;">City</label>
                    <select name="city" class="form-select">
                        <option value="">All Cities</option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}" {{ ($city ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <!--  State/Province -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-600 mb-1" style="font-weight:600; font-size:.78rem; color:#374151;">State/Province</label>
                    <select name="state" class="form-select">
                        <option value="">All States</option>
                        @foreach($states as $s)
                            <option value="{{ $s }}" {{ ($state ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <!--  Country -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-600 mb-1" style="font-weight:600; font-size:.78rem; color:#374151;">Country</label>
                    <select name="country" class="form-select">
                        <option value="">All Countries</option>
                        @foreach($countries as $co)
                            <option value="{{ $co }}" {{ ($country ?? '') === $co ? 'selected' : '' }}>{{ $co }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Category -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-600 mb-1" style="font-weight:600; font-size:.78rem; color:#374151;">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $filterCategory)
                            <option value="{{ $filterCategory->id }}" {{ (string) ($category ?? '') === (string) $filterCategory->id ? 'selected' : '' }}>{{ $filterCategory->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!--  Per page -->
                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-600 mb-1" style="font-weight:600; font-size:.78rem; color:#374151;">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([25,50,100,200,500,1000] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <!--  Buttons -->
                <div class="col-6 col-md-4 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="{{ route('persons.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>
</div>

{{-- ── Actions bar ── --}}
<div class="action-bar mb-2">
    <div class="d-flex align-items-center gap-2">
        <span class="results-txt">
            Showing <strong>{{ $persons->firstItem() ?? 0 }}–{{ $persons->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($persons->total()) }}</strong> results
        </span>
        @if(auth()->user()->isAdmin())
        <button type="button" class="btn btn-danger btn-sm d-none" id="bulkDeleteBtn">
            <i class="bi bi-trash3-fill me-1"></i>Delete Selected (<span id="selectedCount">0</span>)
        </button>
        @endif
    </div>
    <div class="d-flex gap-2">
        @if(!auth()->user()->isViewer())
        <a href="{{ route('persons.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus-fill me-1"></i>Add Member
        </a>
        @endif
        @if(!auth()->user()->isViewer())
        <a href="{{ route('persons.export') }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        @endif
        @if(!auth()->user()->isViewer())
        <a href="{{ route('persons.import.form') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-upload me-1"></i>Import
        </a>
        @endif
    </div>
</div>

{{-- ── Data table ── --}}
<form method="POST" action="{{ route('persons.bulk-destroy') }}" id="bulkForm">
    @csrf
    <div class="card">
        <div class="card-header">
            <div class="card-header-title">
                <i class="bi bi-table" style="background:#eff6ff; color:#3b82f6;"></i>
                Members List
            </div>
            <span style="font-size:.75rem; color:#94a3b8;">
                Page {{ $persons->currentPage() }} of {{ $persons->lastPage() }}
            </span>
        </div>
        <div class="tbl-wrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        @if(auth()->user()->isAdmin())
                        <th style="width:44px; padding:.8rem .5rem .8rem 1rem;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        @endif
                        <th style="width:55px;">#</th>

                        @php
                            $cols = [
                                'first_name' => 'First Name',
                                'last_name'  => 'Last Name',
                                'country'    => 'Country',
                                'categories' => 'Categories',
                            ];
                            if (!auth()->user()->isViewer()) {
                                $cols = array_merge($cols, [
                                    'email'      => 'Email',
                                    'occupation' => 'Occupation',
                                ]);
                            }
                        @endphp

                        @foreach($cols as $col => $label)
                            @if($col === 'categories')
                                <th>{{ $label }}</th>
                                @continue
                            @endif
                            @php
                                $isActive = $sort === $col;
                                $newDir   = ($isActive && $direction === 'asc') ? 'desc' : 'asc';
                                $href     = route('persons.index', array_merge(
                                    request()->except(['sort','direction','page']),
                                    ['sort' => $col, 'direction' => $newDir]
                                ));
                            @endphp
                            <th>
                                <a href="{{ $href }}" class="th-sort {{ $isActive ? 'active' : '' }}">
                                    {{ $label }}
                                    @if($isActive)
                                        <i class="bi bi-caret-{{ $direction === 'asc' ? 'up' : 'down' }}-fill arrow on"></i>
                                    @else
                                        <i class="bi bi-caret-down-fill arrow"></i>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        <th style="width:120px;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($persons as $person)
                        <tr>
                            @if(auth()->user()->isAdmin())
                            <td style="padding:.72rem .5rem .72rem 1rem;">
                                <input type="checkbox" name="ids[]" value="{{ $person->id }}"
                                       class="form-check-input row-cb">
                            </td>
                            @endif
                            <td class="text-muted" style="font-size:.75rem;">{{ $person->id }}</td>
                            <td>
                                <a href="{{ route('persons.show', $person) }}" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                                    @if($person->headshot)
                                        <img src="{{ $person->headshot_url }}"
                                             alt="" class="rounded-circle"
                                             style="width:30px; height:30px; object-fit:cover;">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                                             style="width:30px; height:30px; background:#e5e7eb; color:#6b7280; font-size:.7rem; font-weight:600;">
                                            {{ strtoupper(substr($person->first_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <strong>{{ $person->first_name }}</strong>
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('persons.show', $person) }}" class="text-decoration-none text-dark">
                                    {{ $person->last_name }}
                                </a>
                            </td>
                            <td style="font-size:.82rem;">{{ $person->country ?? '—' }}</td>
                            <td style="font-size:.82rem;">
                                @forelse($person->categories as $memberCategory)
                                    <span class="badge bg-light text-dark border me-1">{{ $memberCategory->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            @if(!auth()->user()->isViewer())
                            <td>
                                @if($person->email)
                                    <a href="mailto:{{ $person->email }}"
                                       class="text-decoration-none text-primary" style="font-size:.82rem;">
                                        {{ $person->email }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($person->occupation)
                                    <span class="badge" style="background:#fff7ed; color:#c2410c; font-size:.72rem;">
                                        {{ Str::limit($person->occupation, 30) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endif
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-nowrap">
                                <a href="{{ route('persons.show', $person) }}"
                                   class="btn btn-act btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(!auth()->user()->isViewer())
                                <a href="{{ route('persons.edit', $person) }}"
                                   class="btn btn-act btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->isAdmin())
                                <button type="button"
                                        class="btn btn-act btn-outline-danger del-btn"
                                        data-id="{{ $person->id }}"
                                        data-name="{{ $person->first_name }} {{ $person->last_name }}"
                                        title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-30"></i>
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

{{-- Pagination --}}
@if($persons->hasPages())
    <div class="mt-4">
        {{ $persons->links() }}
    </div>
@endif

{{-- ── Delete modal ── --}}
<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:38px; height:38px; background:#fef2f2; border-radius:10px;
                                display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:1rem;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <h5 class="modal-title fw-700 mb-0" style="font-size:.95rem; font-weight:700;">Delete Member</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2" style="font-size:.875rem;">
                Are you sure you want to delete <strong id="delName"></strong>?
                <br><small class="text-danger">This action cannot be undone.</small>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* Single delete */
    document.querySelectorAll('.del-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('delName').textContent = this.dataset.name;
            document.getElementById('delForm').action = '{{ url("dashboard") }}/' + this.dataset.id;
            new bootstrap.Modal(document.getElementById('delModal')).show();
        });
    });

    /* Select-all / bulk */
    var selAll  = document.getElementById('selectAll');
    var cbs     = document.querySelectorAll('.row-cb');
    var bulkBtn = document.getElementById('bulkDeleteBtn');
    var cnt     = document.getElementById('selectedCount');

    function refresh() {
        var n = document.querySelectorAll('.row-cb:checked').length;
        cnt.textContent = n;
        bulkBtn.classList.toggle('d-none', n === 0);
    }

    if (selAll) {
        selAll.addEventListener('change', function () {
            cbs.forEach(function (c) { c.checked = selAll.checked; });
            refresh();
        });
    }
    cbs.forEach(function (c) { c.addEventListener('change', refresh); });

    if (bulkBtn) {
        bulkBtn.addEventListener('click', function () {
            var n = document.querySelectorAll('.row-cb:checked').length;
            if (confirm('Delete ' + n + ' selected records? This cannot be undone.')) {
                document.getElementById('bulkForm').submit();
            }
        });
    }
})();
</script>
@endpush
