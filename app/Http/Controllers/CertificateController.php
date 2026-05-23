<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Media;
use App\Models\Person;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->isViewer()) abort(403);

        $request->validate([
            'search' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|in:25,50,100',
        ]);

        $search = trim((string) $request->input('search'));
        $perPage = (int) $request->input('per_page', 25);

        $certificates = Certificate::query()
            ->with(['person', 'pdfMedia'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('certificate_number', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhereHas('person', function ($personQuery) use ($like) {
                            $personQuery->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like);
                        });
                });
            })
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('certificates.index', compact('certificates', 'search', 'perPage'));
    }

    public function create(Request $request)
    {
        if (auth()->user()->isViewer()) abort(403);

        $persons = Person::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $selectedPersonId = $request->integer('person_id');

        return view('certificates.create', compact('persons', 'selectedPersonId'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->isViewer()) abort(403);

        $data = $this->validatedData($request);
        $data['certificate_number'] = $data['certificate_number'] ?: Certificate::generateCertificateNumber();
        $data['verify_token'] = Certificate::generateUniqueToken();
        $data['issued_by'] = auth()->id();

        $certificate = Certificate::create($data);

        return redirect()->route('certificates.edit', $certificate)
            ->with('success', 'Certificate issued successfully.');
    }

    public function edit(Certificate $certificate)
    {
        if (auth()->user()->isViewer()) abort(403);

        $certificate->load(['person', 'pdfMedia']);
        $persons = Person::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('certificates.edit', compact('certificate', 'persons'));
    }

    public function update(Request $request, Certificate $certificate)
    {
        if (auth()->user()->isViewer()) abort(403);

        $data = $this->validatedData($request, $certificate);
        $data['certificate_number'] = $data['certificate_number'] ?: $certificate->certificate_number;

        $certificate->update($data);

        return redirect()->route('certificates.edit', $certificate)
            ->with('success', 'Certificate updated successfully.');
    }

    public function destroy(Certificate $certificate)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $certificate->delete();

        return redirect()->route('certificates.index')
            ->with('success', 'Certificate deleted successfully.');
    }

    public function qr(Certificate $certificate)
    {
        if (auth()->user()->isViewer()) abort(403);

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=svg&data=' . urlencode($certificate->verify_url);
        $svg = @file_get_contents($qrUrl);

        if ($svg === false || trim($svg) === '') {
            abort(502, 'QR code service is currently unavailable.');
        }

        $filename = 'certificate-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', $certificate->certificate_number) . '-qr.svg';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function validatedData(Request $request, ?Certificate $certificate = null): array
    {
        $data = $request->validate([
            'person_id' => 'required|integer|exists:persons,id',
            'certificate_number' => 'nullable|string|max:100|unique:certificates,certificate_number,' . ($certificate?->id ?? 'NULL'),
            'title' => 'nullable|string|max:255',
            'issued_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
            'pdf_media_id' => 'nullable|integer|exists:media,id',
        ]);

        if (!empty($data['pdf_media_id'])) {
            $media = Media::findOrFail($data['pdf_media_id']);

            if ($media->mime_type !== 'application/pdf') {
                abort(422, 'The selected file must be a PDF.');
            }
        }

        return $data;
    }
}
