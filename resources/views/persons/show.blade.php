@extends('layouts.app')

@section('title', $person->first_name . ' ' . $person->last_name)
@section('page-title', $person->first_name . ' ' . $person->last_name)
@section('page-sub', 'Member profile details')
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('persons.index') }}" class="text-decoration-none text-muted">Members</a>
    </li>
    <li class="breadcrumb-item active">{{ $person->first_name }} {{ $person->last_name }}</li>
@endsection

@section('content')
<style>
    .profile-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px 16px 0 0;
        padding: 2.5rem 2rem 4.5rem;
        position: relative;
        overflow: hidden;
    }
    .profile-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .profile-avatar-wrap {
        position: relative;
        margin-top: -4rem;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .profile-avatar {
        width: 120px; height: 120px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,.15);
        object-fit: cover;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 2.5rem; font-weight: 700;
    }
    .profile-avatar img {
        width: 100%; height: 100%;
        border-radius: 50%; object-fit: cover;
    }
    .info-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        transition: all .2s ease;
        border: 1px solid #f1f5f9;
    }
    .info-card:hover {
        background: #f1f5f9;
        border-color: #e2e8f0;
    }
    .info-label {
        font-size: .68rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .7px;
        margin-bottom: 4px;
    }
    .info-value {
        font-weight: 600;
        color: #1e293b;
        font-size: .9rem;
        word-break: break-word;
    }
    .info-value a {
        text-decoration: none;
        color: #3b82f6;
    }
    .info-value a:hover { text-decoration: underline; }
    .info-value .text-muted { font-weight: 400; }
    .social-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 500;
        text-decoration: none;
        transition: all .2s;
        border: 1px solid #e2e8f0;
        color: #475569;
        background: #fff;
    }
    .social-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.08); color: #1e293b; }
    .social-btn i { font-size: 1rem; }
    .section-title {
        font-size: .75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .8px;
        padding-bottom: 6px;
        border-bottom: 2px solid #f1f5f9;
        margin-bottom: .8rem;
    }
    .bio-text {
        font-size: .9rem;
        color: #334155;
        line-height: 1.7;
        white-space: pre-wrap;
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        <div class="card border-0 shadow-sm" style="border-radius:16px; overflow:hidden;">

            {{-- Hero gradient --}}
            <div class="profile-hero">
                <div class="d-flex justify-content-end gap-2 position-relative" style="z-index:2;">
                    @if(!auth()->user()->isViewer())
                    <a href="{{ route('persons.edit', $person) }}"
                       class="btn btn-sm btn-light" style="border-radius:8px;">
                        <i class="bi bi-pencil-fill me-1"></i>Edit
                    </a>
                    @endif
                    @if(auth()->user()->isAdmin())
                    <button type="button" class="btn btn-sm btn-outline-light" id="delBtn" style="border-radius:8px;">
                        <i class="bi bi-trash3-fill me-1"></i>Delete
                    </button>
                    @endif
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                {{-- Avatar + Name --}}
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar">
                        @if($person->headshot)
                            <img src="{{ $person->headshot_url }}" alt="{{ $person->first_name }}">
                        @else
                            {{ strtoupper(substr($person->first_name, 0, 1)) }}{{ strtoupper(substr($person->last_name, 0, 1)) }}
                        @endif
                    </div>
                    <h4 class="mt-3 mb-0 fw-bold text-center" style="color:#1e293b;">
                        {{ $person->first_name }} {{ $person->last_name }}
                    </h4>
                    @if($person->occupation)
                        <p class="text-muted mb-0 text-center" style="font-size:.9rem;">{{ $person->occupation }}</p>
                    @endif
                    <span class="badge bg-light text-secondary border mt-2" style="font-size:.72rem;">Member #{{ $person->id }}</span>
                </div>

                {{-- Social Media Links --}}
                @if($person->facebook || $person->instagram || $person->linkedin || $person->twitter)
                <div class="text-center mt-3 d-flex flex-wrap gap-2 justify-content-center">
                    @if($person->facebook)
                        <a href="{{ $person->facebook }}" target="_blank" rel="noopener" class="social-btn" style="color:#1877f2; border-color:#dbeafe;">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                    @endif
                    @if($person->instagram)
                        <a href="{{ $person->instagram }}" target="_blank" rel="noopener" class="social-btn" style="color:#e1306c; border-color:#fce4ec;">
                            <i class="bi bi-instagram"></i> Instagram
                        </a>
                    @endif
                    @if($person->linkedin)
                        <a href="{{ $person->linkedin }}" target="_blank" rel="noopener" class="social-btn" style="color:#0a66c2; border-color:#dbeafe;">
                            <i class="bi bi-linkedin"></i> LinkedIn
                        </a>
                    @endif
                    @if($person->twitter)
                        <a href="{{ $person->twitter }}" target="_blank" rel="noopener" class="social-btn" style="color:#000; border-color:#e2e8f0;">
                            <i class="bi bi-twitter-x"></i> X
                        </a>
                    @endif
                </div>
                @endif

                <hr class="my-4" style="border-color:#f1f5f9;">

                {{-- â”€â”€ Contact Information â”€â”€ --}}
                @if(!auth()->user()->isViewer())
                <div class="section-title"><i class="bi bi-envelope me-1"></i> Contact Information</div>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                @if($person->email)
                                    <a href="mailto:{{ $person->email }}">{{ $person->email }}</a>
                                @else <span class="text-muted">â€”</span> @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card" style="background:#fffbeb; border-color:#fef3c7;">
                            <div class="info-label">WAEN Email</div>
                            <div class="info-value">
                                @if($person->waen_email)
                                    <a href="mailto:{{ $person->waen_email }}">{{ $person->waen_email }}</a>
                                @else <span class="text-muted">â€”</span> @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card" style="background:#f0fdf4; border-color:#dcfce7;">
                            <div class="info-label">WhatsApp</div>
                            <div class="info-value">
                                @if($person->whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^\d]/','',$person->whatsapp) }}"
                                       target="_blank" rel="noopener" class="text-success">
                                        <i class="bi bi-whatsapp me-1"></i>{{ $person->whatsapp }}
                                    </a>
                                @else <span class="text-muted">â€”</span> @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Phone</div>
                            <div class="info-value">
                                @if($person->phone)
                                    <a href="tel:{{ $person->phone }}">{{ $person->phone }}</a>
                                @else <span class="text-muted">â€”</span> @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- â”€â”€ Personal Details â”€â”€ --}}
                <div class="section-title"><i class="bi bi-person me-1"></i> Personal Details</div>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">First Name</div>
                            <div class="info-value">{{ $person->first_name }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Last Name</div>
                            <div class="info-value">{{ $person->last_name }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value">
                                {{ $person->date_of_birth ? $person->date_of_birth->format('M d, Y') : 'â€”' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Occupation</div>
                            <div class="info-value">{{ $person->occupation ?: 'â€”' }}</div>
                        </div>
                    </div>                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Education</div>
                            <div class="info-value">{{ $person->education ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Gender</div>
                            <div class="info-value">{{ $person->gender ?: '—' }}</div>
                        </div>
                    </div>                </div>

                {{-- â”€â”€ Address â”€â”€ --}}
                <div class="section-title"><i class="bi bi-geo-alt me-1"></i> Address</div>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="info-card">
                            <div class="info-label">Street Address</div>
                            <div class="info-value">
                                {{ $person->street_address ?: 'â€”' }}
                                @if($person->apartment)
                                    <span class="text-muted">({{ $person->apartment }})</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">City</div>
                            <div class="info-value">{{ $person->city ?: 'â€”' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">State/Province</div>
                            <div class="info-value">{{ $person->state_province ?: 'â€”' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">ZIP</div>
                            <div class="info-value">{{ $person->zip_code ?: 'â€”' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-card">
                            <div class="info-label">Country</div>
                            <div class="info-value">{{ $person->country ?: 'â€”' }}</div>
                        </div>
                    </div>
                </div>

                {{-- â”€â”€ Biography â”€â”€ --}}
                @if($person->biography)
                <div class="section-title"><i class="bi bi-journal-text me-1"></i> Biography</div>
                <div class="info-card mb-4">
                    <div class="bio-text">{{ $person->biography }}</div>
                </div>
                @endif

                {{-- â”€â”€ Professional / Expertise â”€â”€ --}}
                @if($person->areas_of_expertise || $person->proposed_initiatives || $person->cv_file)
                <div class="section-title"><i class="bi bi-briefcase me-1"></i> Professional Details</div>
                <div class="row g-3 mb-4">
                    @if($person->areas_of_expertise)
                    <div class="col-12">
                        <div class="info-card">
                            <div class="info-label">Areas of Expertise</div>
                            <div class="info-value" style="white-space:pre-wrap; font-weight:500;">{{ $person->areas_of_expertise }}</div>
                        </div>
                    </div>
                    @endif
                    @if($person->proposed_initiatives)
                    <div class="col-12">
                        <div class="info-card">
                            <div class="info-label">Proposed Initiatives or Programs</div>
                            <div class="info-value" style="white-space:pre-wrap; font-weight:500;">{{ $person->proposed_initiatives }}</div>
                        </div>
                    </div>
                    @endif
                    @if($person->cv_file)
                    <div class="col-12">
                        <div class="info-card d-flex align-items-center gap-3">
                            <div style="width:44px; height:44px; background:#fef2f2; border-radius:10px;
                                        display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:1.2rem;">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </div>
                            <div>
                                <div class="info-label mb-0">CV / Resume</div>
                                <a href="{{ $person->cv_url }}" target="_blank" class="text-decoration-none fw-semibold" style="font-size:.85rem;">
                                    <i class="bi bi-download me-1"></i>Download CV
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <div class="section-title"><i class="bi bi-patch-check me-1"></i> Certificates</div>
                <div class="info-card mb-4">
                    @if($person->certificates->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Certificate No.</th>
                                        <th>Title</th>
                                        <th>Issued</th>
                                        @if(!auth()->user()->isViewer())
                                            <th>PDF</th>
                                            <th>QR</th>
                                            <th></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($person->certificates as $certificate)
                                        <tr>
                                            <td class="fw-semibold">{{ $certificate->certificate_number }}</td>
                                            <td>{{ $certificate->title ?: '—' }}</td>
                                            <td>{{ $certificate->issued_at?->format('M d, Y') ?: '—' }}</td>
                                            @if(!auth()->user()->isViewer())
                                                <td>
                                                    @if($certificate->pdfMedia)
                                                        <a href="{{ route('media.download', $certificate->pdfMedia) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('certificates.qr', $certificate) }}" class="btn btn-sm btn-outline-secondary">QR</a>
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('certificates.edit', $certificate) }}" class="btn btn-sm btn-primary">Edit</a>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(!auth()->user()->isViewer())
                            <div class="mt-3">
                                <a href="{{ route('certificates.create', ['person_id' => $person->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Issue New Certificate
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted">No certificates have been issued for this person yet.</span>
                            @if(!auth()->user()->isViewer())
                                <a href="{{ route('certificates.create', ['person_id' => $person->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Issue Certificate
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- â”€â”€ Timestamps â”€â”€ --}}
                @if(!auth()->user()->isViewer())
                <hr style="border-color:#f1f5f9;">
                <div class="d-flex justify-content-between flex-wrap" style="font-size:.75rem; color:#94a3b8;">
                    <span><i class="bi bi-calendar-plus me-1"></i>Added {{ $person->created_at->format('M d, Y  H:i') }}</span>
                    <span><i class="bi bi-pencil me-1"></i>Updated {{ $person->updated_at->format('M d, Y  H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('persons.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>
</div>

{{-- Delete modal --}}
<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:38px; height:38px; background:#fef2f2; border-radius:10px;
                                display:flex; align-items:center; justify-content:center; color:#ef4444;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <h5 class="modal-title mb-0" style="font-size:.95rem; font-weight:700;">Delete Member</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2" style="font-size:.875rem;">
                Are you sure you want to delete
                <strong>{{ $person->first_name }} {{ $person->last_name }}</strong>?
                <br><small class="text-danger">This action cannot be undone.</small>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('persons.destroy', $person) }}">
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
document.getElementById('delBtn')?.addEventListener('click', function () {
    new bootstrap.Modal(document.getElementById('delModal')).show();});
</script>
@endpush
