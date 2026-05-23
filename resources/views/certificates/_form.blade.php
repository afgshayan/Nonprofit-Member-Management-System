@csrf

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Person <span class="text-danger">*</span></label>
        <div class="position-relative" id="person-picker">
            <input type="hidden" name="person_id" id="person_id" value="{{ old('person_id', $certificate->person_id ?? $selectedPersonId ?? '') }}">

            <button type="button" id="person-picker-toggle" class="form-select text-start @error('person_id') is-invalid @enderror" style="height:auto;">
                <span id="person-picker-label">
                    @php
                        $selectedPersonLabel = '— Select person —';
                        $selectedPersonIdValue = (string) old('person_id', $certificate->person_id ?? $selectedPersonId ?? '');
                        foreach ($persons as $personOption) {
                            if ((string) $personOption->id === $selectedPersonIdValue) {
                                $selectedPersonLabel = $personOption->first_name . ' ' . $personOption->last_name;
                                break;
                            }
                        }
                    @endphp
                    {{ $selectedPersonLabel }}
                </span>
            </button>

            <div id="person-picker-menu" class="position-absolute top-100 start-0 w-100 bg-white border shadow-sm d-none" style="z-index:1050; max-height:320px; overflow:hidden;">
                <div class="p-2 border-bottom">
                    <input type="text" id="person-picker-search" class="form-control" placeholder="Search person name...">
                </div>
                <div id="person-picker-options" style="max-height:260px; overflow:auto;">
                    @foreach($persons as $personOption)
                        <button
                            type="button"
                            class="person-picker-option w-100 text-start border-0 bg-white px-3 py-2"
                            data-id="{{ $personOption->id }}"
                            data-label="{{ $personOption->first_name }} {{ $personOption->last_name }}">
                            {{ $personOption->first_name }} {{ $personOption->last_name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        @error('person_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Issued Date <span class="text-danger">*</span></label>
        <input type="date" name="issued_at" class="form-control @error('issued_at') is-invalid @enderror" value="{{ old('issued_at', isset($certificate) && $certificate->issued_at ? $certificate->issued_at->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('issued_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Certificate Number</label>
        <input type="text" name="certificate_number" class="form-control @error('certificate_number') is-invalid @enderror" value="{{ old('certificate_number', $certificate->certificate_number ?? '') }}" maxlength="100" placeholder="Leave blank to auto-generate">
        @error('certificate_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Title</label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $certificate->title ?? '') }}" maxlength="255" placeholder="e.g. Completion Certificate">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Certificate PDF</label>
        <input type="hidden" name="pdf_media_id" id="pdf_media_id" value="{{ old('pdf_media_id', $certificate->pdf_media_id ?? '') }}">

        <div id="pdf-current" class="mb-2 d-flex align-items-center gap-2{{ old('pdf_media_id', $certificate->pdf_media_id ?? null) || (isset($certificate) && $certificate->pdfMedia) ? '' : ' d-none' }}">
            <div style="width:44px; height:44px; background:#f1f5f9; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                <i class="bi bi-file-earmark-pdf-fill" style="font-size:1.4rem; color:#ef4444;"></i>
            </div>
            <div>
                <div id="pdf-current-name" class="fw-semibold" style="font-size:.85rem;">
                    {{ $certificate->pdfMedia->original_name ?? 'Selected PDF' }}
                </div>
                @if(isset($certificate) && $certificate->pdfMedia)
                    <a href="{{ route('media.download', $certificate->pdfMedia) }}" class="text-primary" style="font-size:.75rem;">Download current PDF</a>
                @endif
            </div>
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm" id="pdf-pick-btn">
            <i class="bi bi-file-earmark-plus me-1"></i>{{ isset($certificate) && $certificate->pdfMedia ? 'Change PDF' : 'Choose PDF' }}
        </button>
        <small class="d-block text-muted mt-1">This PDF stays private inside the dashboard and never appears on the public verify page.</small>
        @error('pdf_media_id') <div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div> @enderror
    </div>

    @if(isset($certificate))
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Verify Link</label>
            <input type="text" class="form-control" value="{{ $certificate->verify_url }}" readonly>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-end gap-2">
            <a href="{{ route('certificates.qr', $certificate) }}" class="btn btn-outline-secondary"><i class="bi bi-qr-code me-1"></i>Download QR</a>
            <a href="{{ $certificate->verify_url }}" target="_blank" class="btn btn-outline-secondary">Open Verify Page</a>
        </div>
    @endif

    <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" maxlength="5000">{{ old('notes', $certificate->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@include('media._picker_modal')

@push('scripts')
<script>
(function () {
    var personPicker = document.getElementById('person-picker');
    var personToggle = document.getElementById('person-picker-toggle');
    var personMenu = document.getElementById('person-picker-menu');
    var personSearch = document.getElementById('person-picker-search');
    var personLabel = document.getElementById('person-picker-label');
    var personInput = document.getElementById('person_id');
    var personOptions = Array.from(document.querySelectorAll('.person-picker-option'));

    var pickBtn = document.getElementById('pdf-pick-btn');
    var current = document.getElementById('pdf-current');
    var currentName = document.getElementById('pdf-current-name');
    var input = document.getElementById('pdf_media_id');

    if (personPicker && personToggle && personMenu && personSearch && personLabel && personInput) {
        personToggle.addEventListener('click', function () {
            personMenu.classList.toggle('d-none');
            if (!personMenu.classList.contains('d-none')) {
                personSearch.focus();
            }
        });

        personSearch.addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            personOptions.forEach(function (option) {
                option.classList.toggle('d-none', term !== '' && !option.dataset.label.toLowerCase().includes(term));
            });
        });

        personOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                personInput.value = this.dataset.id;
                personLabel.textContent = this.dataset.label;
                personMenu.classList.add('d-none');
                personSearch.value = '';
                personOptions.forEach(function (item) { item.classList.remove('d-none'); });
            });
        });

        document.addEventListener('click', function (event) {
            if (!personPicker.contains(event.target)) {
                personMenu.classList.add('d-none');
            }
        });
    }

    if (!pickBtn) return;

    pickBtn.addEventListener('click', function () {
        MediaPicker.open({
            type: 'document',
            title: 'Choose Certificate PDF',
            onSelect: function (media) {
                input.value = media.id;
                current.classList.remove('d-none');
                currentName.textContent = media.original_name;
                pickBtn.innerHTML = '<i class="bi bi-file-earmark-plus me-1"></i>Change PDF';
            }
        });
    });
})();
</script>
@endpush
