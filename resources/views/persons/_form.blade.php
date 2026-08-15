{{-- Shared form fields — used in both create and edit --}}
<div class="row g-3">

    {{-- ── Personal Information ── --}}
    <div class="col-12">
        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-person me-1"></i> Personal Information</h6>
        <hr class="mt-1 mb-0" style="border-color:#e5e7eb;">
    </div>

    {{-- First Name --}}
    <div class="col-12 col-md-4">
        <label for="first_name" class="form-label fw-semibold" style="font-size:.875rem;">
            First Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="first_name" id="first_name"
               class="form-control @error('first_name') is-invalid @enderror"
               value="{{ old('first_name', $person->first_name ?? '') }}"
               maxlength="100" required autocomplete="given-name"
               placeholder="e.g. John">
        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Last Name --}}
    <div class="col-12 col-md-4">
        <label for="last_name" class="form-label fw-semibold" style="font-size:.875rem;">
            Last Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="last_name" id="last_name"
               class="form-control @error('last_name') is-invalid @enderror"
               value="{{ old('last_name', $person->last_name ?? '') }}"
               maxlength="100" required autocomplete="family-name"
               placeholder="e.g. Smith">
        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Date of Birth --}}
    <div class="col-12 col-md-4">
        <label for="date_of_birth" class="form-label fw-semibold" style="font-size:.875rem;">Date of Birth</label>
        <input type="date" name="date_of_birth" id="date_of_birth"
               class="form-control @error('date_of_birth') is-invalid @enderror"
               value="{{ old('date_of_birth', isset($person) && $person->date_of_birth ? $person->date_of_birth->format('Y-m-d') : '') }}">
        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Occupation --}}
    <div class="col-12 col-md-6">
        <label for="occupation" class="form-label fw-semibold" style="font-size:.875rem;">Current Occupation / Organization</label>
        <input type="text" name="occupation" id="occupation"
               class="form-control @error('occupation') is-invalid @enderror"
               value="{{ old('occupation', $person->occupation ?? '') }}"
               maxlength="200" placeholder="e.g. Software Engineer at Google">
        @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Categories --}}
    <div class="col-12 col-md-6">
        <label for="category_ids" class="form-label fw-semibold" style="font-size:.875rem;">Categories</label>
        @php($selectedCategoryIds = old('category_ids', isset($person) ? $person->categories->pluck('id')->all() : []))
        <select name="category_ids[]" id="category_ids" multiple size="4"
                class="form-select @error('category_ids') is-invalid @enderror">
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ in_array($category->id, $selectedCategoryIds) ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple categories. <a href="{{ route('categories.index') }}">Manage categories</a></small>
        @error('category_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @error('category_ids.*') <div class="text-danger" style="font-size:.82rem;">{{ $message }}</div> @enderror
    </div>

    {{-- Education --}}
    <div class="col-12 col-md-3">
        <label for="education" class="form-label fw-semibold" style="font-size:.875rem;">Education</label>
        <select name="education" id="education"
                class="form-select @error('education') is-invalid @enderror">
            <option value="">— Select —</option>
            @foreach(['High School','Associate\'s Degree','Bachelor\'s Degree','Master\'s Degree','PhD / Doctorate','Professional Degree','Other'] as $edu)
                <option value="{{ $edu }}" {{ old('education', $person->education ?? '') === $edu ? 'selected' : '' }}>{{ $edu }}</option>
            @endforeach
        </select>
        @error('education') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Gender --}}
    <div class="col-12 col-md-3">
        <label for="gender" class="form-label fw-semibold" style="font-size:.875rem;">Gender</label>
        <select name="gender" id="gender"
                class="form-select @error('gender') is-invalid @enderror">
            <option value="">— Select —</option>
            @foreach(['Male','Female','Other'] as $g)
                <option value="{{ $g }}" {{ old('gender', $person->gender ?? '') === $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
        </select>
        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Headshot --}}
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold" style="font-size:.875rem;">
            <i class="bi bi-camera me-1"></i>Headshot Photo
        </label>
        <input type="hidden" name="headshot_media_id" id="headshot_media_id" value="">
        <input type="hidden" name="headshot_remove" id="headshot_remove_input" value="0">

        {{-- Current / Selected preview --}}
        <div id="headshot-current"
             class="mb-2 position-relative d-inline-block{{ (isset($person) && $person->headshot) ? '' : ' d-none' }}"
             style="max-width:80px;">
            <img id="headshot-current-img"
                 src="{{ (isset($person) && $person->headshot) ? $person->headshot_url : '' }}"
                 alt="Headshot"
                 style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid #e5e7eb; display:block;">
            {{-- Trash overlay --}}
            <button type="button" class="btn btn-sm btn-danger position-absolute"
                    id="headshot-remove-btn"
                    style="top:-4px; right:-4px; width:24px; height:24px; border-radius:50%; padding:0;
                           display:flex; align-items:center; justify-content:center; font-size:.7rem;
                           box-shadow:0 1px 4px rgba(0,0,0,.2);"
                    title="Remove photo">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </div>

        <div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="headshot-pick-btn">
                <i class="bi bi-image me-1"></i>{{ (isset($person) && $person->headshot) ? 'Change Image' : 'Choose Image' }}
            </button>
        </div>
        <small class="text-muted">Select from Media Library or upload new. JPG, PNG, WebP. Max 5 MB.</small>
        @error('headshot_media_id') <div class="text-danger" style="font-size:.82rem;">{{ $message }}</div> @enderror
    </div>

    {{-- ── Contact Information ── --}}
    <div class="col-12 mt-3">
        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-envelope me-1"></i> Contact Information</h6>
        <hr class="mt-1 mb-0" style="border-color:#e5e7eb;">
    </div>

    {{-- Email --}}
    <div class="col-12 col-md-6">
        <label for="email" class="form-label fw-semibold" style="font-size:.875rem;">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $person->email ?? '') }}"
                   maxlength="191" autocomplete="email"
                   placeholder="example@domain.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- WAEN Email --}}
    <div class="col-12 col-md-6">
        <label for="waen_email" class="form-label fw-semibold" style="font-size:.875rem;">WAEN Email Address</label>
        <div class="input-group">
            <span class="input-group-text" style="background:#fff3cd; border-color:#ffc107; color:#856404;">
                <i class="bi bi-envelope-at"></i>
            </span>
            <input type="email" name="waen_email" id="waen_email"
                   class="form-control @error('waen_email') is-invalid @enderror"
                   value="{{ old('waen_email', $person->waen_email ?? '') }}"
                   maxlength="191"
                   placeholder="name@waen.org">
            @error('waen_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- WhatsApp --}}
    <div class="col-12 col-md-6">
        <label for="whatsapp" class="form-label fw-semibold" style="font-size:.875rem;">WhatsApp</label>
        <div class="input-group">
            <span class="input-group-text" style="background:#dcfce7; border-color:#bbf7d0; color:#16a34a;">
                <i class="bi bi-whatsapp"></i>
            </span>
            <input type="tel" name="whatsapp" id="whatsapp"
                   class="form-control @error('whatsapp') is-invalid @enderror"
                   value="{{ old('whatsapp', $person->whatsapp ?? '') }}"
                   maxlength="30"
                   placeholder="+1 234 567 8900">
            @error('whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- Phone --}}
    <div class="col-12 col-md-6">
        <label for="phone" class="form-label fw-semibold" style="font-size:.875rem;">Phone</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="tel" name="phone" id="phone"
                   class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $person->phone ?? '') }}"
                   maxlength="30" autocomplete="tel"
                   placeholder="+1 234 567 8900">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- ── Address ── --}}
    <div class="col-12 mt-3">
        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt me-1"></i> Address</h6>
        <hr class="mt-1 mb-0" style="border-color:#e5e7eb;">
    </div>

    {{-- Street Address --}}
    <div class="col-12 col-md-8">
        <label for="street_address" class="form-label fw-semibold" style="font-size:.875rem;">Street Address</label>
        <input type="text" name="street_address" id="street_address"
               class="form-control @error('street_address') is-invalid @enderror"
               value="{{ old('street_address', $person->street_address ?? '') }}"
               maxlength="255" placeholder="123 Main Street">
        @error('street_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Apartment --}}
    <div class="col-12 col-md-4">
        <label for="apartment" class="form-label fw-semibold" style="font-size:.875rem;">Apartment, suite, etc.</label>
        <input type="text" name="apartment" id="apartment"
               class="form-control @error('apartment') is-invalid @enderror"
               value="{{ old('apartment', $person->apartment ?? '') }}"
               maxlength="100" placeholder="Apt 4B">
        @error('apartment') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- City --}}
    <div class="col-12 col-md-3">
        <label for="city" class="form-label fw-semibold" style="font-size:.875rem;">City</label>
        <input type="text" name="city" id="city"
               class="form-control @error('city') is-invalid @enderror"
               value="{{ old('city', $person->city ?? '') }}"
               maxlength="100" placeholder="e.g. New York">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- State/Province --}}
    <div class="col-12 col-md-3">
        <label for="state_province" class="form-label fw-semibold" style="font-size:.875rem;">State/Province</label>
        <input type="text" name="state_province" id="state_province"
               class="form-control @error('state_province') is-invalid @enderror"
               value="{{ old('state_province', $person->state_province ?? '') }}"
               maxlength="100" placeholder="e.g. California">
        @error('state_province') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- ZIP Code --}}
    <div class="col-12 col-md-3">
        <label for="zip_code" class="form-label fw-semibold" style="font-size:.875rem;">ZIP / Postal Code</label>
        <input type="text" name="zip_code" id="zip_code"
               class="form-control @error('zip_code') is-invalid @enderror"
               value="{{ old('zip_code', $person->zip_code ?? '') }}"
               maxlength="20" placeholder="10001">
        @error('zip_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Country --}}
    <div class="col-12 col-md-3">
        <label for="country" class="form-label fw-semibold" style="font-size:.875rem;">Country</label>
        <input type="text" name="country" id="country"
               class="form-control @error('country') is-invalid @enderror"
               value="{{ old('country', $person->country ?? '') }}"
               maxlength="100" list="country-list"
               placeholder="e.g. United States">
        <datalist id="country-list">
            @foreach(['Afghanistan','Albania','Algeria','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahrain','Bangladesh','Belarus','Belgium','Bolivia','Bosnia and Herzegovina','Brazil','Bulgaria','Cambodia','Canada','Chile','China','Colombia','Croatia','Cuba','Cyprus','Czech Republic','Denmark','Ecuador','Egypt','Ethiopia','Finland','France','Georgia','Germany','Ghana','Greece','Hungary','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Japan','Jordan','Kazakhstan','Kenya','Kuwait','Kyrgyzstan','Latvia','Lebanon','Libya','Lithuania','Malaysia','Mexico','Moldova','Morocco','Netherlands','New Zealand','Nigeria','North Macedonia','Norway','Oman','Pakistan','Palestine','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Saudi Arabia','Serbia','Singapore','Slovakia','South Africa','South Korea','Spain','Sri Lanka','Sudan','Sweden','Switzerland','Syria','Tajikistan','Thailand','Tunisia','Turkey','Turkmenistan','Ukraine','United Arab Emirates','United Kingdom','United States','Uzbekistan','Venezuela','Vietnam','Yemen'] as $c)
                <option value="{{ $c }}">
            @endforeach
        </datalist>
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- ── Social Media ── --}}
    <div class="col-12 mt-3">
        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-share me-1"></i> Social Media</h6>
        <hr class="mt-1 mb-0" style="border-color:#e5e7eb;">
    </div>

    {{-- Facebook --}}
    <div class="col-12 col-md-6">
        <label for="facebook" class="form-label fw-semibold" style="font-size:.875rem;">Facebook</label>
        <div class="input-group">
            <span class="input-group-text" style="background:#e8f0fe; color:#1877f2;"><i class="bi bi-facebook"></i></span>
            <input type="url" name="facebook" id="facebook"
                   class="form-control @error('facebook') is-invalid @enderror"
                   value="{{ old('facebook', $person->facebook ?? '') }}"
                   maxlength="255" placeholder="https://facebook.com/username">
            @error('facebook') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- Instagram --}}
    <div class="col-12 col-md-6">
        <label for="instagram" class="form-label fw-semibold" style="font-size:.875rem;">Instagram</label>
        <div class="input-group">
            <span class="input-group-text" style="background:#fce4ec; color:#e1306c;"><i class="bi bi-instagram"></i></span>
            <input type="url" name="instagram" id="instagram"
                   class="form-control @error('instagram') is-invalid @enderror"
                   value="{{ old('instagram', $person->instagram ?? '') }}"
                   maxlength="255" placeholder="https://instagram.com/username">
            @error('instagram') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- LinkedIn --}}
    <div class="col-12 col-md-6">
        <label for="linkedin" class="form-label fw-semibold" style="font-size:.875rem;">LinkedIn</label>
        <div class="input-group">
            <span class="input-group-text" style="background:#e8f4fd; color:#0a66c2;"><i class="bi bi-linkedin"></i></span>
            <input type="url" name="linkedin" id="linkedin"
                   class="form-control @error('linkedin') is-invalid @enderror"
                   value="{{ old('linkedin', $person->linkedin ?? '') }}"
                   maxlength="255" placeholder="https://linkedin.com/in/username">
            @error('linkedin') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- X (Twitter) --}}
    <div class="col-12 col-md-6">
        <label for="twitter" class="form-label fw-semibold" style="font-size:.875rem;">X (Twitter)</label>
        <div class="input-group">
            <span class="input-group-text" style="background:#f0f0f0; color:#000;"><i class="bi bi-twitter-x"></i></span>
            <input type="url" name="twitter" id="twitter"
                   class="form-control @error('twitter') is-invalid @enderror"
                   value="{{ old('twitter', $person->twitter ?? '') }}"
                   maxlength="255" placeholder="https://x.com/username">
            @error('twitter') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- ── Professional Details ── --}}
    <div class="col-12 mt-3">
        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-briefcase me-1"></i> Professional Details</h6>
        <hr class="mt-1 mb-0" style="border-color:#e5e7eb;">
    </div>

    {{-- Biography --}}
    <div class="col-12">
        <label for="biography" class="form-label fw-semibold" style="font-size:.875rem;">Biography</label>
        <textarea name="biography" id="biography" rows="4"
                  class="form-control @error('biography') is-invalid @enderror"
                  maxlength="5000"
                  placeholder="Brief biography of the member …">{{ old('biography', $person->biography ?? '') }}</textarea>
        @error('biography') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- CV --}}
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold" style="font-size:.875rem;">
            <i class="bi bi-file-earmark-pdf me-1"></i>CV / Resume
        </label>
        <input type="hidden" name="cv_media_id" id="cv_media_id" value="">
        <input type="hidden" name="cv_remove" id="cv_remove_input" value="0">

        {{-- Current / Selected preview --}}
        <div id="cv-current"
             class="mb-2 d-flex align-items-center gap-2{{ (isset($person) && $person->cv_file) ? '' : ' d-none' }}">
            <div style="width:44px; height:44px; background:#f1f5f9; border-radius:10px;
                        display:flex; align-items:center; justify-content:center; position:relative;">
                <i class="bi bi-file-earmark-pdf-fill" style="font-size:1.4rem; color:#ef4444;" id="cv-current-icon"></i>
                {{-- Trash overlay --}}
                <button type="button" class="btn btn-sm btn-danger position-absolute"
                        id="cv-remove-btn"
                        style="top:-6px; right:-6px; width:20px; height:20px; border-radius:50%; padding:0;
                               display:flex; align-items:center; justify-content:center; font-size:.6rem;
                               box-shadow:0 1px 4px rgba(0,0,0,.2);"
                        title="Remove CV">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </div>
            <div style="min-width:0;">
                <div id="cv-current-name" class="fw-semibold text-truncate" style="font-size:.8rem; max-width:200px;">
                    {{ isset($person) && $person->cv_file ? basename($person->cv_file) : '' }}
                </div>
                @if(isset($person) && $person->cv_file)
                    <a href="{{ $person->cv_url }}" target="_blank" class="text-primary" style="font-size:.72rem;">
                        <i class="bi bi-download"></i> Download
                    </a>
                @endif
            </div>
        </div>

        <div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="cv-pick-btn">
                <i class="bi bi-file-earmark-plus me-1"></i>{{ (isset($person) && $person->cv_file) ? 'Change File' : 'Choose File' }}
            </button>
        </div>
        <small class="text-muted">Select from Media Library or upload new. PDF, DOC, DOCX. Max 10 MB.</small>
        @error('cv_media_id') <div class="text-danger" style="font-size:.82rem;">{{ $message }}</div> @enderror
    </div>

    {{-- Areas of Expertise --}}
    <div class="col-12 col-md-6">
        <label for="areas_of_expertise" class="form-label fw-semibold" style="font-size:.875rem;">Areas of Expertise</label>
        <textarea name="areas_of_expertise" id="areas_of_expertise" rows="3"
                  class="form-control @error('areas_of_expertise') is-invalid @enderror"
                  maxlength="5000"
                  placeholder="e.g. Machine Learning, Public Health, Policy …">{{ old('areas_of_expertise', $person->areas_of_expertise ?? '') }}</textarea>
        @error('areas_of_expertise') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Proposed Initiatives --}}
    <div class="col-12">
        <label for="proposed_initiatives" class="form-label fw-semibold" style="font-size:.875rem;">Proposed Initiatives or Programs</label>
        <textarea name="proposed_initiatives" id="proposed_initiatives" rows="3"
                  class="form-control @error('proposed_initiatives') is-invalid @enderror"
                  maxlength="5000"
                  placeholder="Describe any proposed initiatives or programs …">{{ old('proposed_initiatives', $person->proposed_initiatives ?? '') }}</textarea>
        @error('proposed_initiatives') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

</div>

{{-- Include media picker modal --}}
@include('media._picker_modal')

<script>
(function() {
    'use strict';

    // ── Headshot picker ──
    var headshotPickBtn = document.getElementById('headshot-pick-btn');
    var headshotRemoveBtn = document.getElementById('headshot-remove-btn');
    var headshotMediaId = document.getElementById('headshot_media_id');
    var headshotRemoveInput = document.getElementById('headshot_remove_input');
    var headshotCurrent = document.getElementById('headshot-current');
    var headshotImg = document.getElementById('headshot-current-img');

    if (headshotPickBtn) {
        headshotPickBtn.addEventListener('click', function() {
            MediaPicker.open({
                type: 'image',
                title: 'Choose Headshot Photo',
                onSelect: function(media) {
                    headshotMediaId.value = media.id;
                    headshotRemoveInput.value = '0';
                    headshotImg.src = media.url;
                    headshotCurrent.classList.remove('d-none');
                    headshotPickBtn.innerHTML = '<i class="bi bi-image me-1"></i>Change Image';
                }
            });
        });
    }

    if (headshotRemoveBtn) {
        headshotRemoveBtn.addEventListener('click', function() {
            headshotMediaId.value = '';
            headshotRemoveInput.value = '1';
            headshotCurrent.classList.add('d-none');
            headshotPickBtn.innerHTML = '<i class="bi bi-image me-1"></i>Choose Image';
        });
    }

    // ── CV picker ──
    var cvPickBtn = document.getElementById('cv-pick-btn');
    var cvRemoveBtn = document.getElementById('cv-remove-btn');
    var cvMediaId = document.getElementById('cv_media_id');
    var cvRemoveInput = document.getElementById('cv_remove_input');
    var cvCurrent = document.getElementById('cv-current');
    var cvCurrentName = document.getElementById('cv-current-name');
    var cvCurrentIcon = document.getElementById('cv-current-icon');

    if (cvPickBtn) {
        cvPickBtn.addEventListener('click', function() {
            MediaPicker.open({
                type: '',
                title: 'Choose CV / Resume',
                onSelect: function(media) {
                    cvMediaId.value = media.id;
                    cvRemoveInput.value = '0';
                    cvCurrentName.textContent = media.original_name;
                    // Update icon based on extension
                    if (media.is_image) {
                        cvCurrentIcon.className = 'bi bi-file-earmark-image-fill';
                        cvCurrentIcon.style.color = '#8b5cf6';
                    } else if (media.extension === 'pdf') {
                        cvCurrentIcon.className = 'bi bi-file-earmark-pdf-fill';
                        cvCurrentIcon.style.color = '#ef4444';
                    } else {
                        cvCurrentIcon.className = 'bi bi-file-earmark-word-fill';
                        cvCurrentIcon.style.color = '#2563eb';
                    }
                    cvCurrent.classList.remove('d-none');
                    cvPickBtn.innerHTML = '<i class="bi bi-file-earmark-plus me-1"></i>Change File';
                }
            });
        });
    }

    if (cvRemoveBtn) {
        cvRemoveBtn.addEventListener('click', function() {
            cvMediaId.value = '';
            cvRemoveInput.value = '1';
            cvCurrent.classList.add('d-none');
            cvPickBtn.innerHTML = '<i class="bi bi-file-earmark-plus me-1"></i>Choose File';
        });
    }
})();
</script>
