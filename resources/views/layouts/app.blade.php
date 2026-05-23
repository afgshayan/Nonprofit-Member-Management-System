<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <title>@yield('title', 'Members') — {{ $appName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand:       #f97316;
            --brand-dark:  #ea580c;
            --brand-light: #fff7ed;
            --brand-mid:   #fed7aa;
            --sb-width:    240px;
            --topbar-h:    62px;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6fb;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ─── SIDEBAR ─────────────────────────────── */
        #sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sb-width);
            background: #ffffff;
            border-right: 1px solid #e8edf3;
            z-index: 1000;
            display: flex; flex-direction: column;
            transition: transform .3s ease;
            overflow-y: auto;
        }

        /* Brand / logo */
        .sb-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 20px 18px 16px;
            border-bottom: 1px solid #f1f5f9;
            flex-shrink: 0;
        }
        .sb-logo {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem; color: #fff; flex-shrink: 0;
        }
        .sb-title { font-size: .95rem; font-weight: 700; color: #1e293b; letter-spacing: 1.5px; }
        .sb-sub   { font-size: .65rem; color: #94a3b8; margin-top: 1px; }

        /* Section labels */
        .sb-section { padding: .9rem .85rem .1rem; }
        .sb-section-lbl {
            font-size: .6rem; font-weight: 700; letter-spacing: 1.4px;
            text-transform: uppercase; color: #94a3b8;
            padding: 0 6px; margin-bottom: 4px;
        }

        /* Nav links */
        .sb-nav { list-style: none; margin: 0; padding: 0; }
        .sb-nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px;
            color: #64748b; text-decoration: none;
            font-size: .835rem; font-weight: 500;
            transition: background .14s, color .14s;
            margin-bottom: 2px;
        }
        .sb-nav a i { font-size: .95rem; width: 20px; text-align: center; flex-shrink: 0; }
        .sb-nav a:hover { background: #f8fafc; color: #1e293b; }
        .sb-nav a.active {
            background: var(--brand-light);
            color: var(--brand-dark);
            font-weight: 600;
        }
        .sb-nav a.active i { color: var(--brand); }

        /* User footer */
        .sb-footer {
            margin-top: auto; padding: 12px 14px;
            border-top: 1px solid #f1f5f9; flex-shrink: 0;
        }
        .user-pill {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px;
            background: #f8fafc; margin-bottom: 8px;
        }
        .user-ava {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--brand), #f43f5e);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .78rem; font-weight: 700; flex-shrink: 0;
        }
        .user-nm { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.2; }
        .user-em { font-size: .67rem; color: #94a3b8; }
        .btn-signout {
            width: 100%; background: #fff0ef; border: none;
            border-radius: 9px; padding: 8px;
            color: #ef4444; font-size: .78rem; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            cursor: pointer; transition: background .14s;
        }
        .btn-signout:hover { background: #fee2e2; }

        /* ─── MAIN AREA ────────────────────────────── */
        #main { margin-left: var(--sb-width); min-height: 100vh; display: flex; flex-direction: column; }

        /* ─── TOP BAR ──────────────────────────────── */
        #topbar {
            position: sticky; top: 0;
            background: #ffffff;
            border-bottom: 1px solid #e8edf3;
            height: var(--topbar-h);
            padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between;
            z-index: 900; gap: 16px;
        }

        /* Left: hamburger + title */
        .tb-left { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .mobile-btn { display: none; background: none; border: none; font-size: 1.2rem; color: #64748b; padding: 0; }
        .tb-page-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
        .tb-page-sub   { font-size: .68rem; color: #94a3b8; }

        /* Center: search */
        .tb-search {
            flex: 1; max-width: 380px;
            position: relative;
        }
        .tb-search i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: .9rem; pointer-events: none;
        }
        .tb-search input {
            width: 100%; padding: 8px 12px 8px 36px;
            border: 1.5px solid #e8edf3; border-radius: 10px;
            font-size: .84rem; font-family: inherit;
            background: #f8fafc; color: #1e293b;
            outline: none; transition: border-color .2s, background .2s;
        }
        .tb-search input:focus { border-color: var(--brand); background: #fff; }
        .tb-search input::placeholder { color: #b0bac6; }

        /* Right: icons + avatar */
        .tb-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .tb-icon-btn {
            width: 36px; height: 36px;
            background: #f8fafc; border: 1.5px solid #e8edf3;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; font-size: 1rem; cursor: pointer; text-decoration: none;
            transition: background .14s, color .14s;
        }
        .tb-icon-btn:hover { background: var(--brand-light); color: var(--brand); border-color: var(--brand-mid); }
        .tb-user {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 10px 5px 6px;
            border: 1.5px solid #e8edf3; border-radius: 10px;
            background: #f8fafc; cursor: pointer;
            text-decoration: none; color: inherit;
        }
        .tb-user:hover { background: var(--brand-light); border-color: var(--brand-mid); }
        .tb-user-ava {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, var(--brand), #f43f5e);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .72rem; font-weight: 700;
        }
        .tb-user-name { font-size: .78rem; font-weight: 600; color: #1e293b; }
        .tb-user-role { font-size: .63rem; color: #94a3b8; }

        /* ─── PAGE CONTENT ─────────────────────────── */
        .page-body { padding: 24px; flex: 1; }

        /* ─── STAT CARDS ───────────────────────────── */
        .stat-card {
            background: #fff;
            border: 1px solid #e8edf3;
            padding: 20px 22px;
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.07); transform: translateY(-2px); }
        .stat-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.c-blue::before   { background: #3b82f6; }
        .stat-card.c-green::before  { background: #22c55e; }
        .stat-card.c-orange::before { background: var(--brand); }
        .stat-card.c-purple::before { background: #a855f7; }
        .stat-card.c-teal::before   { background: #14b8a6; }

        .stat-label {
            font-size: .73rem; font-weight: 600; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .6px; margin-bottom: 10px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .stat-label i { font-size: 1rem; }
        .c-blue   .stat-label i { color: #3b82f6; }
        .c-green  .stat-label i { color: #22c55e; }
        .c-orange .stat-label i { color: var(--brand); }
        .c-purple .stat-label i { color: #a855f7; }
        .c-teal   .stat-label i { color: #14b8a6; }

        .stat-val { font-size: 1.75rem; font-weight: 700; color: #1e293b; line-height: 1; }
        .stat-sub { font-size: .73rem; color: #94a3b8; margin-top: 5px; }

        /* ─── GENERIC CARD ─────────────────────────── */
        .card {
            border: 1px solid #e8edf3 !important;
            box-shadow: none;
        }
        .card-header {
            background: #fff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            border-radius: 14px 14px 0 0 !important;
            padding: 14px 18px !important;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header-title {
            font-size: .875rem; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 8px;
        }
        .card-header-title i {
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem;
        }

        /* ─── TABLE ────────────────────────────────── */
        .tbl-wrap { overflow-x: auto; }
        .table { margin: 0; font-size: .84rem; }
        .table thead th {
            background: #f8fafc; color: #475569;
            font-weight: 600; font-size: .71rem;
            text-transform: uppercase; letter-spacing: .7px;
            white-space: nowrap; padding: 11px 14px;
            border-bottom: 2px solid #e8edf3; border-top: none;
        }
        .table tbody td {
            padding: 12px 14px; vertical-align: middle;
            border-color: #f1f5f9; color: #334155;
        }
        .table tbody tr:hover { background: #fafbff; }
        .table tbody tr:last-child td { border-bottom: none; }

        .th-sort { color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px; }
        .th-sort:hover  { color: var(--brand); }
        .th-sort.active { color: var(--brand-dark); }
        .th-sort .arrow { font-size: .65rem; opacity: .35; }
        .th-sort .arrow.on { opacity: 1; color: var(--brand); }

        /* ─── BUTTONS ───────────────────────────────── */
        .btn { font-weight: 500; font-size: .84rem; }
        .btn-sm { font-size: .77rem; padding: .3rem .65rem; }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            border: none; color: #fff;
        }
        .btn-primary:hover { background: linear-gradient(135deg, var(--brand-dark), #c2410c); color: #fff; }
        .btn-act {
            width: 26px; height: 26px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .72rem;
        }

        /* ─── FORM CONTROLS ─────────────────────────── */
        .form-control, .form-select {
            border-radius: 9px; border: 1.5px solid #e2e8f0;
            font-size: .84rem; padding: .5rem .75rem; color: #1e293b;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(249,115,22,.12);
        }
        .input-group-text {
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-right: none; color: #94a3b8;
        }
        .input-group .form-control { border-radius: 0 9px 9px 0; border-left-color: #e2e8f0; }

        /* ─── ALERTS ────────────────────────────────── */
        .alert { font-size: .875rem; border: none; }
        .alert-success { background: #f0fdf4; color: #166534; }
        .alert-danger   { background: #fef2f2; color: #991b1b; }

        /* ─── PAGINATION ─────────────────────────────── */
        .pagination { gap: 2px; justify-content: center; flex-wrap: wrap; }
        .pagination .page-link {
            border-radius: 8px !important; border: 1.5px solid #e2e8f0;
            color: #475569; font-size: .78rem; padding: .32rem .65rem;
        }
        .pagination .page-link:hover { background: var(--brand-light); border-color: var(--brand-mid); color: var(--brand-dark); }
        .pagination .page-item.active .page-link { background: var(--brand); border-color: var(--brand); }

        /* ─── ACTION BAR ─────────────────────────────── */
        .action-bar {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px; margin-bottom: 14px;
        }
        .results-txt { font-size: .8rem; color: #64748b; }
        .results-txt strong { color: #1e293b; }

        /* ─── FILTER CARD ────────────────────────────── */
        .filter-card {
            background: #fff;
            border: 1px solid #e8edf3;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        /* ─── OVERLAY ────────────────────────────────── */
        #sb-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,.4); z-index: 999;
        }

        /* ─── RESPONSIVE ─────────────────────────────── */
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #sb-overlay.open { display: block; }
            #main { margin-left: 0; }
            .mobile-btn { display: block; }
            .tb-search { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>

<div id="sb-overlay"></div>

{{-- ════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════ --}}
<nav id="sidebar">

    <div class="sb-brand">
        <div class="sb-logo"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="sb-title">{{ $appName }}</div>
            <div class="sb-sub">{{ $appDesc }}</div>
        </div>
    </div>

    <div class="sb-section">
        <div class="sb-section-lbl">Main</div>
        <ul class="sb-nav">
            <li>
                <a href="{{ route('persons.index') }}"
                   class="{{ request()->routeIs('persons.index') ? 'active' : '' }}">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('persons.index') }}"
                   class="{{ request()->routeIs('persons.show','persons.edit') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> All Members
                </a>
            </li>
            @if(!auth()->user()->isViewer())
            <li>
                <a href="{{ route('persons.create') }}"
                   class="{{ request()->routeIs('persons.create') ? 'active' : '' }}">
                    <i class="bi bi-person-plus-fill"></i> Add Member
                </a>
            </li>
            @endif
        </ul>
    </div>

    <div class="sb-section">
        <div class="sb-section-lbl">Data Tools</div>
        <ul class="sb-nav">
            @if(!auth()->user()->isViewer())
            <li>
                <a href="{{ route('certificates.index') }}"
                   class="{{ request()->routeIs('certificates.*') ? 'active' : '' }}">
                    <i class="bi bi-patch-check-fill"></i> Certificates
                </a>
            </li>
            <li>
                <a href="{{ route('media.index') }}"
                   class="{{ request()->routeIs('media.*') ? 'active' : '' }}">
                    <i class="bi bi-images"></i> Media Library
                </a>
            </li>
            <li>
                <a href="{{ route('persons.import.form') }}"
                   class="{{ request()->routeIs('persons.import.*') ? 'active' : '' }}">
                    <i class="bi bi-cloud-upload-fill"></i> Import CSV
                </a>
            </li>
            @endif
            @if(!auth()->user()->isViewer())
            <li>
                <a href="{{ route('persons.export', request()->only(['search','province','education'])) }}">
                    <i class="bi bi-cloud-download-fill"></i> Export CSV
                </a>
            </li>
            @endif
        </ul>
    </div>

    @if(auth()->user()->isAdmin())
    <div class="sb-section">
        <div class="sb-section-lbl">Access</div>
        <ul class="sb-nav">
            <li>
                <a href="{{ route('users.index') }}"
                   class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-lock"></i> Users
                </a>
            </li>
        </ul>
    </div>
    <div class="sb-section">
        <div class="sb-section-lbl">System</div>
        <ul class="sb-nav">
            <li>
                <a href="{{ route('settings.index') }}"
                   class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="bi bi-sliders2-vertical"></i> Settings
                </a>
            </li>
            <li>
                <a href="{{ route('update.index') }}"
                   class="{{ request()->routeIs('update.*') ? 'active' : '' }}"
                   @if($updateAvailable) style="color:#16a34a; font-weight:600;" @endif>
                    <i class="bi bi-cloud-arrow-down{{ $updateAvailable ? '-fill' : '' }}"
                       @if($updateAvailable) style="color:#16a34a;" @endif></i>
                    Updates
                    @if($updateAvailable)
                    <span class="ms-auto badge" style="background:#dcfce7; color:#15803d; font-size:.6rem; padding:2px 6px; border-radius:6px;">v{{ $updateVersion }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </div>
    @endif

    <div class="sb-footer">
        <div class="user-pill">
            <div class="user-ava">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
            <div style="min-width:0; overflow:hidden;">
                <div class="user-nm text-truncate">{{ auth()->user()->name }}</div>
                <div class="user-em text-truncate">{{ auth()->user()->roleLabel() }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-signout">
                <i class="bi bi-box-arrow-left"></i> Sign Out
            </button>
        </form>
    </div>
</nav>

{{-- ════════════════════════════════════════
     MAIN
════════════════════════════════════════ --}}
<div id="main">

    {{-- TOP BAR --}}
    <header id="topbar">
        {{-- Left --}}
        <div class="tb-left">
            <button class="mobile-btn" id="mobileBtn"><i class="bi bi-list"></i></button>
            <div>
                <div class="tb-page-title">@yield('page-title', 'Dashboard')</div>
                <div class="tb-page-sub">@yield('page-sub', $appName)</div>
            </div>
        </div>

        {{-- Center: search --}}
        <div class="tb-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search members, provinces …"
                   id="topbarSearch" autocomplete="off">
        </div>

        {{-- Right --}}
        <div class="tb-right">
            @if(!auth()->user()->isViewer())
            <a href="{{ route('persons.create') }}" class="tb-icon-btn" title="Add Member">
                <i class="bi bi-person-plus-fill"></i>
            </a>
            <a href="{{ route('persons.import.form') }}" class="tb-icon-btn" title="Import CSV">
                <i class="bi bi-cloud-upload"></i>
            </a>
            @endif
            @if(!auth()->user()->isViewer())
            <a href="{{ route('persons.export', request()->only(['search','province','education'])) }}"
               class="tb-icon-btn" title="Export CSV">
                <i class="bi bi-download"></i>
            </a>
            @endif
            <div style="width:1px; height:22px; background:#e2e8f0; margin:0 2px;"></div>
            <a class="tb-user text-decoration-none" href="{{ route('settings.index') }}">
                <div class="tb-user-ava">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                <div class="d-none d-lg-block">
                    <div class="tb-user-name">{{ auth()->user()->name }}</div>
                    <div class="tb-user-role">{{ auth()->user()->roleLabel() }}</div>
                </div>
                <i class="bi bi-chevron-down d-none d-lg-block" style="font-size:.65rem; color:#94a3b8;"></i>
            </a>
        </div>
    </header>

    {{-- Update available banner (admin only) --}}
    @if($updateAvailable)
    <div class="px-4 pt-3">
        <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-3" style="background:#f0fdf4; border:1.5px solid #86efac;">
            <i class="bi bi-cloud-arrow-down-fill text-success fs-5 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <span class="fw-600" style="color:#15803d; font-size:.875rem;">New version available: <strong>v{{ $updateVersion }}</strong></span>
                <span class="text-muted ms-2" style="font-size:.78rem;">A new update is ready to install.</span>
            </div>
            <a href="{{ route('update.index') }}" class="btn btn-sm" style="background:#16a34a; color:#fff; border-radius:8px; font-size:.78rem; white-space:nowrap;">
                <i class="bi bi-cloud-arrow-down me-1"></i>View Update
            </a>
        </div>
    </div>
    @endif

    {{-- Update available banner (admin only) --}}
    @if($updateAvailable)
    <div class="px-4 pt-3">
        <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-3" style="background:#f0fdf4; border:1.5px solid #86efac;">
            <i class="bi bi-cloud-arrow-down-fill text-success fs-5 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <span class="fw-semibold" style="color:#15803d; font-size:.875rem;">New version available: <strong>v{{ $updateVersion }}</strong></span>
                <span class="text-muted ms-2" style="font-size:.78rem;">A new update is ready to install.</span>
            </div>
            <a href="{{ route('update.index') }}" class="btn btn-sm" style="background:#16a34a; color:#fff; border-radius:8px; font-size:.78rem; white-space:nowrap;">
                <i class="bi bi-cloud-arrow-down me-1"></i>View Update
            </a>
        </div>
    </div>
    @endif

    {{-- Flash / Validation alerts --}}
    @if(session('success'))
        <div class="px-4 pt-3">
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="px-4 pt-3">
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 mb-0">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    {{-- Page content --}}
    <main class="page-body">@yield('content')</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>
(function () {
    var sb  = document.getElementById('sidebar');
    var ov  = document.getElementById('sb-overlay');
    var btn = document.getElementById('mobileBtn');
    function openSb()  { sb.classList.add('open'); ov.classList.add('open'); }
    function closeSb() { sb.classList.remove('open'); ov.classList.remove('open'); }
    if (btn) btn.addEventListener('click', openSb);
    if (ov)  ov.addEventListener('click', closeSb);

    /* Topbar search → redirect to persons index */
    var ts = document.getElementById('topbarSearch');
    if (ts) {
        ts.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                window.location.href = '{{ route('persons.index') }}?search=' + encodeURIComponent(this.value.trim());
            }
        });
    }
})();
</script>
@stack('modals')
@stack('scripts')
</body>
</html>
