@extends('layouts.app')

@section('title', 'Import Certificates')
@section('page-title', 'Import Certificates')
@section('page-sub', 'Upload a CSV file (and the PDFs it references) to bulk-issue certificates')
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('certificates.index') }}" class="text-decoration-none text-muted">Certificates</a>
    </li>
    <li class="breadcrumb-item active">Import CSV</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">

        {{-- Row-level problems from the last import --}}
        @if(session('import_errors'))
            <div class="card mb-4" style="border-color:#fecaca;">
                <div class="card-header d-flex align-items-center gap-2" style="background:#fef2f2; color:#991b1b;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Skipped rows ({{ count(session('import_errors')) }})</span>
                </div>
                <div class="tbl-wrap" style="max-height:320px; overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0" style="font-size:.84rem;">
                        <thead>
                            <tr>
                                <th style="width:90px;">CSV row</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('import_errors') as $err)
                                <tr>
                                    <td class="fw-semibold">{{ $err['row'] }}</td>
                                    <td>{{ $err['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Upload card --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <div style="width:32px; height:32px; background:linear-gradient(135deg,#6b7280,#374151);
                            border-radius:8px; display:flex; align-items:center; justify-content:center;
                            color:#fff; font-size:.85rem; flex-shrink:0;">
                    <i class="bi bi-upload"></i>
                </div>
                <span>Upload Certificates CSV</span>
            </div>
            <div class="card-body p-4">

                <div class="alert d-flex gap-3 mb-4"
                     style="background:#eff6ff; color:#1e40af; border:none; border-radius:10px;">
                    <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
                    <div>
                        <strong>Requirements</strong>
                        <ul class="mb-0 mt-1 ps-3" style="font-size:.84rem;">
                            <li>First row must be the header. Each row must identify the member using
                                <code>person_id</code>, <code>email</code>, or <code>first_name</code> + <code>last_name</code>
                                (checked in that order; the name must match exactly one member).
                            </li>
                            <li>Optional columns:
                                <code>certificate_number</code>, <code>title</code>, <code>issued_at</code>,
                                <code>notes</code>, <code>pdf_file</code>
                            </li>
                            <li><code>issued_at</code> format: <code>YYYY-MM-DD</code> — today's date is used when empty.</li>
                            <li><code>certificate_number</code> is generated automatically when empty. Existing numbers are skipped.</li>
                            <li><code>pdf_file</code> can be the full path on your computer
                                (e.g. <code>C:\Certificates\ahmad.pdf</code>) or just the file name.
                                The file is matched <strong>by its name</strong> against the PDFs you select below.
                            </li>
                            <li>Encoding: <strong>UTF-8</strong> (with or without BOM). Max CSV size: <strong>50 MB</strong>.</li>
                        </ul>
                    </div>
                </div>

                <form method="POST" action="{{ route('certificates.import') }}"
                      enctype="multipart/form-data" id="importForm" novalidate>
                    @csrf

                    <div class="mb-4">
                        <label for="csv_file" class="form-label fw-semibold" style="font-size:.875rem;">
                            1. Select CSV File <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="csv_file" id="csv_file"
                               class="form-control @error('csv_file') is-invalid @enderror"
                               accept=".csv,.txt" required>
                        @error('csv_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.875rem;">
                            2. Select the PDF files <span class="text-muted fw-normal">(optional — only needed if the CSV has a <code>pdf_file</code> column)</span>
                        </label>
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="pickFolderBtn">
                                <i class="bi bi-folder2-open me-1"></i>Choose Folder
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="pickFilesBtn">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Choose PDF Files
                            </button>
                        </div>
                        <input type="file" id="pdf_folder" class="d-none" webkitdirectory directory multiple>
                        <input type="file" name="pdf_files[]" id="pdf_files" class="d-none" accept=".pdf,application/pdf" multiple>
                        <div id="pdfSummary" class="form-text" style="font-size:.8rem;">No PDF files selected.</div>
                        @error('pdf_files.*')
                            <div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="pdf_zip" class="form-label" style="font-size:.84rem;">
                            …or upload the PDFs as a single <strong>ZIP</strong> file
                            <span class="text-muted">(recommended for more than {{ $maxFileUploads }} files)</span>
                        </label>
                        <input type="file" name="pdf_zip" id="pdf_zip"
                               class="form-control @error('pdf_zip') is-invalid @enderror" accept=".zip">
                        @error('pdf_zip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text" style="font-size:.78rem;">Max ZIP size: 50 MB. Subfolders inside the ZIP are fine.</div>
                    </div>

                    <div id="uploadLimitWarning" class="alert alert-warning d-none" style="font-size:.84rem;"></div>
                    <div id="missingPdfWarning" class="alert alert-warning d-none" style="font-size:.84rem;"></div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                        <a href="{{ route('certificates.import.sample') }}" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Sample CSV
                        </a>
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="bi bi-upload me-1"></i>Start Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sample card --}}
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text text-muted"></i>
                <span>Sample CSV Template</span>
            </div>
            <div class="card-body p-4">
                <pre class="p-3 rounded-3 mb-2" style="background:#f8fafc; font-size:.8rem; color:#374151; border:1px solid #e5e7eb; overflow-x:auto;">person_id,email,first_name,last_name,certificate_number,title,issued_at,notes,pdf_file
,ahmad@example.com,,,CERT-2026-0101,Leadership Training,2026-05-15,,C:\Certificates\ahmad-rahimi.pdf
,,Sara,Karimi,,Volunteer Appreciation,2026-06-01,Issued at annual meeting,sara-karimi.pdf
12,,,,,Membership Certificate,,,</pre>
                <small class="text-muted" style="font-size:.78rem;">
                    <i class="bi bi-lightbulb me-1 text-warning"></i>
                    Browsers cannot read files from your computer by path, so select the folder containing the PDFs
                    (or upload them as a ZIP) — each <em>pdf_file</em> value is matched to a selected file by name.
                    Rows whose PDF is not found are skipped and listed after the import.
                </small>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var maxUploads   = {{ (int) $maxFileUploads }};
    var folderInput  = document.getElementById('pdf_folder');
    var filesInput   = document.getElementById('pdf_files');
    var summary      = document.getElementById('pdfSummary');
    var warning      = document.getElementById('uploadLimitWarning');
    var submitBtn    = document.getElementById('uploadBtn');

    document.getElementById('pickFolderBtn').addEventListener('click', function () { folderInput.click(); });
    document.getElementById('pickFilesBtn').addEventListener('click', function () { filesInput.click(); });

    // Copy only the PDFs into the submitted input (folders may contain other files)
    function setPdfs(fileList) {
        var dt = new DataTransfer();
        Array.prototype.forEach.call(fileList, function (f) {
            if (/\.pdf$/i.test(f.name)) dt.items.add(f);
        });
        filesInput.files = dt.files;
        refresh();
    }

    function refresh() {
        var count = filesInput.files.length;
        summary.textContent = count ? count + ' PDF file(s) selected.' : 'No PDF files selected.';

        // The CSV itself and the optional ZIP also count toward the server limit
        var limit = maxUploads - 2;
        if (maxUploads > 0 && count > limit) {
            warning.classList.remove('d-none');
            warning.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' +
                'The server accepts at most <strong>' + limit + '</strong> PDF files per upload, but ' + count +
                ' are selected. Please put the PDFs in a <strong>ZIP</strong> file and upload that instead.';
            submitBtn.disabled = true;
        } else {
            warning.classList.add('d-none');
            submitBtn.disabled = false;
        }
    }

    folderInput.addEventListener('change', function () { setPdfs(folderInput.files); });
    filesInput.addEventListener('change', refresh);

    // ── Compare the PDFs referenced in the CSV with the selected files ──
    var csvInput   = document.getElementById('csv_file');
    var zipInput   = document.getElementById('pdf_zip');
    var missingBox = document.getElementById('missingPdfWarning');
    var neededPdfs = [];

    function parseCsv(text) {
        var rows = [], row = [], field = '', inQuotes = false;
        for (var i = 0; i < text.length; i++) {
            var c = text[i];
            if (inQuotes) {
                if (c === '"' && text[i + 1] === '"') { field += '"'; i++; }
                else if (c === '"') inQuotes = false;
                else field += c;
            } else if (c === '"') inQuotes = true;
            else if (c === ',') { row.push(field); field = ''; }
            else if (c === '\n' || c === '\r') {
                if (c === '\r' && text[i + 1] === '\n') i++;
                row.push(field); rows.push(row); row = []; field = '';
            } else field += c;
        }
        if (field !== '' || row.length) { row.push(field); rows.push(row); }
        return rows;
    }

    function baseName(path) {
        return path.trim().split(/[\\/]/).pop();
    }

    function missingPdfs() {
        var selected = {};
        Array.prototype.forEach.call(filesInput.files, function (f) { selected[f.name.toLowerCase()] = true; });
        return neededPdfs.filter(function (name) { return !selected[name.toLowerCase()]; });
    }

    function checkMissing() {
        var missing = missingPdfs();
        if (!missing.length || zipInput.files.length) {
            missingBox.classList.add('d-none');
            return;
        }
        var list = missing.slice(0, 20).map(function (n) {
            return '<li><code>' + n.replace(/[&<>"]/g, function (ch) { return '&#' + ch.charCodeAt(0) + ';'; }) + '</code></li>';
        }).join('');
        missingBox.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' +
            'The CSV references <strong>' + missing.length + '</strong> PDF file(s) that are not selected yet. ' +
            'Use <strong>Choose Folder</strong> or <strong>Choose PDF Files</strong> above (or upload a ZIP):' +
            '<ul class="mb-0 mt-1">' + list + (missing.length > 20 ? '<li>…</li>' : '') + '</ul>';
        missingBox.classList.remove('d-none');
    }

    csvInput.addEventListener('change', function () {
        neededPdfs = [];
        if (!csvInput.files.length) { checkMissing(); return; }

        var reader = new FileReader();
        reader.onload = function () {
            var rows = parseCsv(String(reader.result).replace(/^﻿/, ''));
            var header = (rows.shift() || []).map(function (h) { return h.trim().toLowerCase().replace(/ /g, '_'); });
            var col = -1;
            ['pdf_file', 'pdf_path', 'pdf', 'file', 'file_path'].some(function (alias) {
                col = header.indexOf(alias);
                return col !== -1;
            });
            if (col !== -1) {
                var seen = {};
                rows.forEach(function (r) {
                    var name = baseName(r[col] || '');
                    if (name && !seen[name.toLowerCase()]) { seen[name.toLowerCase()] = true; neededPdfs.push(name); }
                });
            }
            checkMissing();
        };
        reader.readAsText(csvInput.files[0]);
    });

    filesInput.addEventListener('change', checkMissing);
    folderInput.addEventListener('change', checkMissing);
    zipInput.addEventListener('change', checkMissing);

    document.getElementById('importForm').addEventListener('submit', function (e) {
        var missing = zipInput.files.length ? [] : missingPdfs();
        if (missing.length && !confirm(missing.length + ' PDF file(s) referenced in the CSV are not selected, so those rows will be skipped.\n\nImport anyway?')) {
            e.preventDefault();
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Processing…';
    });
})();
</script>
@endpush
