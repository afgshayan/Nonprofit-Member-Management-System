<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Media;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

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

    public function uploadPdf(Request $request, Certificate $certificate)
    {
        if (auth()->user()->isViewer()) abort(403);

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $media = Media::storeUpload($request->file('pdf_file'), auth()->id());
        $certificate->update(['pdf_media_id' => $media->id]);

        if ($request->expectsJson()) {
            return response()->json([
                'success'      => true,
                'download_url' => route('media.download', $media),
            ]);
        }

        return back()->with('success', 'PDF uploaded for certificate ' . $certificate->certificate_number . '.');
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

    // ---------------------------------------------------------------------------
    // Import CSV (with optional PDF attachments)
    // ---------------------------------------------------------------------------

    public function importForm()
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot import records.');

        $maxFileUploads = (int) ini_get('max_file_uploads');

        return view('certificates.import', compact('maxFileUploads'));
    }

    public function importCsv(Request $request)
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot import records.');

        $request->validate([
            'csv_file'    => 'required|file|mimes:csv,txt|max:51200',
            'pdf_files'   => 'nullable|array',
            'pdf_files.*' => 'file|max:20480',
            'pdf_zip'     => 'nullable|file|mimes:zip|max:51200',
        ]);

        // ── Collect available PDFs, keyed by lower-cased file name ──
        $pdfPool = [];

        foreach ($request->file('pdf_files', []) as $file) {
            if (strtolower($file->getClientOriginalExtension()) !== 'pdf') continue;
            $pdfPool[mb_strtolower(self::baseName($file->getClientOriginalName()))] = ['upload' => $file];
        }

        $zip = null;
        if ($request->hasFile('pdf_zip')) {
            $zip = new ZipArchive();
            if ($zip->open($request->file('pdf_zip')->getRealPath()) !== true) {
                return back()->withErrors(['pdf_zip' => 'The ZIP file could not be opened.']);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_ends_with($entry, '/') || str_contains($entry, '__MACOSX')) continue;

                $name = self::baseName($entry);
                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') continue;

                $pdfPool[mb_strtolower($name)] ??= ['zip' => $i, 'name' => $name];
            }
        }

        // ── Read CSV header ──
        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (!$handle) {
            $zip?->close();
            return back()->withErrors(['csv_file' => 'The file could not be read.']);
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $zip?->close();
            return back()->withErrors(['csv_file' => 'The file is empty or has an invalid format.']);
        }

        $header = array_map(fn($h) => strtolower(trim(str_replace(' ', '_', (string) $h))), $header);

        $columnMap = [
            'person_id'          => ['person_id', 'member_id'],
            'email'              => ['email', 'person_email', 'member_email'],
            'first_name'         => ['first_name', 'name'],
            'last_name'          => ['last_name', 'lastname'],
            'certificate_number' => ['certificate_number', 'certificate_no', 'cert_number', 'number'],
            'title'              => ['title', 'certificate_title'],
            'issued_at'          => ['issued_at', 'issue_date', 'issued_date', 'date'],
            'notes'              => ['notes', 'note'],
            'pdf_file'           => ['pdf_file', 'pdf_path', 'pdf', 'file', 'file_path'],
        ];

        $colIndex = [];
        foreach ($columnMap as $field => $aliases) {
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $header);
                if ($idx !== false) {
                    $colIndex[$field] = $idx;
                    break;
                }
            }
        }

        if (!isset($colIndex['person_id']) && !isset($colIndex['email']) && !isset($colIndex['first_name'])) {
            fclose($handle);
            $zip?->close();
            return back()->withErrors(['csv_file' => 'The CSV must contain a person_id, email or first_name column to identify the member.']);
        }

        // ── Process rows ──
        $imported    = 0;
        $withPdf     = 0;
        $rowErrors   = [];
        $rowNumber   = 1;
        $seenNumbers = [];
        $storedPdfs  = []; // pool key => media id (a PDF referenced twice is stored once)

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) continue;

            $g = fn(string $f, int $max = 255) =>
                isset($colIndex[$f]) ? mb_substr(trim((string) ($row[$colIndex[$f]] ?? '')), 0, $max) : '';

            // Resolve the member
            [$person, $personError] = $this->findPersonForImport($g('person_id', 20), $g('email', 191), $g('first_name'), $g('last_name'));
            if (!$person) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => $personError];
                continue;
            }

            // Issue date (defaults to today)
            $issuedAt = now()->toDateString();
            if ($g('issued_at', 30) !== '') {
                try {
                    $issuedAt = Carbon::parse($g('issued_at', 30))->toDateString();
                } catch (\Throwable) {
                    $rowErrors[] = ['row' => $rowNumber, 'message' => 'Invalid issue date "' . $g('issued_at', 30) . '". Use YYYY-MM-DD.'];
                    continue;
                }
            }

            // Certificate number must be unique
            $number = $g('certificate_number', 100);
            if ($number !== '' && (isset($seenNumbers[mb_strtolower($number)]) || Certificate::where('certificate_number', $number)->exists())) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => 'Certificate number "' . $number . '" already exists — row skipped.'];
                continue;
            }

            // PDF lookup: the path written in the CSV is matched by its file name
            $pdfKey  = null;
            $pdfPath = $g('pdf_file', 1000);
            if ($pdfPath !== '') {
                $pdfKey = mb_strtolower(self::baseName($pdfPath));
                if (!isset($pdfPool[$pdfKey])) {
                    $rowErrors[] = ['row' => $rowNumber, 'message' => 'PDF "' . self::baseName($pdfPath) . '" was not found among the uploaded files — row skipped. Select this file (or its folder) in step 2 of the import form, or include it in the ZIP.'];
                    continue;
                }
            }

            try {
                DB::transaction(function () use (
                    $person, $issuedAt, $number, $g, $pdfKey, $pdfPool, $zip, &$storedPdfs, &$seenNumbers, &$imported, &$withPdf
                ) {
                    $mediaId = null;
                    if ($pdfKey !== null) {
                        $mediaId = $storedPdfs[$pdfKey] ??= $this->storeImportedPdf($pdfPool[$pdfKey], $zip)->id;
                    }

                    $certificate = Certificate::create([
                        'person_id'          => $person->id,
                        'certificate_number' => $number !== '' ? $number : Certificate::generateCertificateNumber(),
                        'verify_token'       => Certificate::generateUniqueToken(),
                        'title'              => $g('title') ?: null,
                        'issued_at'          => $issuedAt,
                        'notes'              => $g('notes', 5000) ?: null,
                        'pdf_media_id'       => $mediaId,
                        'issued_by'          => auth()->id(),
                    ]);

                    $seenNumbers[mb_strtolower($certificate->certificate_number)] = true;
                    $imported++;
                    if ($mediaId) $withPdf++;
                });
            } catch (\Throwable $e) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
            }
        }

        fclose($handle);
        $zip?->close();

        $message = "Import completed. {$imported} certificates imported ({$withPdf} with PDF).";
        if ($rowErrors) {
            $message .= ' ' . count($rowErrors) . ' rows skipped — see details below.';

            return redirect()->route('certificates.import.form')
                ->with('success', $message)
                ->with('import_errors', array_slice($rowErrors, 0, 500));
        }

        return redirect()->route('certificates.index')->with('success', $message);
    }

    public function sampleCsv()
    {
        if (auth()->user()->isViewer()) abort(403);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="certificates_sample_import.csv"',
            'Cache-Control'       => 'no-cache',
        ];

        $rows = [
            ['person_id', 'email', 'first_name', 'last_name', 'certificate_number', 'title', 'issued_at', 'notes', 'pdf_file'],
            ['', 'ahmad@example.com', '', '', 'CERT-2026-0101', 'Leadership Training', '2026-05-15', '', 'C:\Certificates\ahmad-rahimi.pdf'],
            ['', '', 'Sara', 'Karimi', '', 'Volunteer Appreciation', '2026-06-01', 'Issued at annual meeting', 'sara-karimi.pdf'],
            ['12', '', '', '', '', 'Membership Certificate', '', '', ''],
        ];

        $callback = function () use ($rows) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($h, $row);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Find a member by ID, then email, then a unique first + last name match.
     *
     * @return array{0: ?Person, 1: ?string}
     */
    private function findPersonForImport(string $id, string $email, string $firstName, string $lastName): array
    {
        if ($id !== '') {
            $person = ctype_digit($id) ? Person::find((int) $id) : null;
            return $person ? [$person, null] : [null, 'No member found with ID "' . $id . '".'];
        }

        if ($email !== '') {
            $person = Person::where('email', strtolower($email))->first();
            return $person ? [$person, null] : [null, 'No member found with email "' . $email . '".'];
        }

        if ($firstName !== '') {
            $matches = Person::where('first_name', $firstName)
                ->when($lastName !== '', fn($q) => $q->where('last_name', $lastName))
                ->limit(2)
                ->get();

            $fullName = trim($firstName . ' ' . $lastName);

            if ($matches->count() === 1) return [$matches->first(), null];
            if ($matches->count() > 1) return [null, 'More than one member is named "' . $fullName . '". Use person_id or email instead.'];

            return [null, 'No member found named "' . $fullName . '".'];
        }

        return [null, 'Missing person_id, email or first_name.'];
    }

    private function storeImportedPdf(array $source, ?ZipArchive $zip): Media
    {
        if (isset($source['upload'])) {
            /** @var UploadedFile $file */
            $file = $source['upload'];
            if ($file->getMimeType() !== 'application/pdf') {
                throw new \RuntimeException('"' . $file->getClientOriginalName() . '" is not a valid PDF file.');
            }

            return Media::storeUpload($file, auth()->id());
        }

        $contents = $zip?->getFromIndex($source['zip']);
        if (!is_string($contents) || !str_starts_with($contents, '%PDF')) {
            throw new \RuntimeException('"' . $source['name'] . '" in the ZIP is not a valid PDF file.');
        }

        $storedPath = 'media/documents/' . Str::random(40) . '.pdf';
        Storage::disk('public')->put($storedPath, $contents);

        return Media::trackExisting($source['name'], 'application/pdf', strlen($contents), $storedPath, auth()->id());
    }

    /**
     * File name from a Windows or Unix style path (e.g. "C:\Certs\a.pdf" → "a.pdf").
     */
    private static function baseName(string $path): string
    {
        $parts = preg_split('#[\\\\/]#', trim($path));
        return (string) end($parts);
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
