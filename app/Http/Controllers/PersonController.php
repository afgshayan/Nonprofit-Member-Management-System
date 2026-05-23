<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Media;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PersonController extends Controller
{
    private const SORT_COLUMNS = ['first_name', 'last_name', 'email', 'city', 'country', 'occupation', 'phone'];

    private const PER_PAGE = 100;

    // ---------------------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------------------

    public function index(Request $request)
    {
        $request->validate([
            'search'    => 'nullable|string|max:200',
            'country'   => 'nullable|string|max:100',
            'city'      => 'nullable|string|max:100',
            'state'     => 'nullable|string|max:100',
            'sort'      => 'nullable|string|in:' . implode(',', self::SORT_COLUMNS),
            'direction' => 'nullable|string|in:asc,desc',
            'per_page'  => 'nullable|integer|in:25,50,100,200,500,1000',
        ]);

        $search    = $request->input('search');
        $country   = $request->input('country');
        $city      = $request->input('city');
        $state     = $request->input('state');
        $sort      = in_array($request->input('sort'), self::SORT_COLUMNS)
                        ? $request->input('sort')
                        : 'first_name';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $defaultPerPage = (int) Setting::get('per_page_default', self::PER_PAGE);
        $perPage   = (int) $request->input('per_page', $defaultPerPage);

        $persons = Person::query()
            ->search($search)
            ->byCountry($country)
            ->byCity($city)
            ->byState($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $pluck = fn($col) => Person::select($col)
            ->whereNotNull($col)->where($col, '!=', '')
            ->distinct()->orderBy($col)->pluck($col);

        $countries = $pluck('country');
        $cities    = $pluck('city');
        $states    = $pluck('state_province');

        $totalCount = Person::count();

        return view('persons.index', compact(
            'persons', 'countries', 'cities', 'states',
            'search', 'country', 'city', 'state',
            'sort', 'direction', 'perPage', 'totalCount'
        ));
    }

    // ---------------------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------------------

    public function create()
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot create records.');
        return view('persons.create');
    }

    // ---------------------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------------------

    public function store(Request $request)
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot create records.');

        $validated = $request->validate(
            Person::validationRules(),
            Person::validationMessages()
        );

        // Handle headshot from media picker
        if ($request->filled('headshot_media_id')) {
            $media = Media::findOrFail($validated['headshot_media_id']);
            $validated['headshot'] = $media->path;
        }

        // Handle CV from media picker
        if ($request->filled('cv_media_id')) {
            $media = Media::findOrFail($validated['cv_media_id']);
            $validated['cv_file'] = $media->path;
        }

        // Remove picker inputs from validated data
        unset($validated['headshot_media_id'], $validated['headshot_remove'],
              $validated['cv_media_id'], $validated['cv_remove']);

        Person::create($validated);

        return redirect()->route('persons.index')
            ->with('success', 'Member created successfully.');
    }

    // ---------------------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------------------

    public function show(Person $person)
    {
        $person->load(['certificates.pdfMedia']);

        return view('persons.show', compact('person'));
    }

    // ---------------------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------------------

    public function edit(Person $person)
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot edit records.');
        return view('persons.edit', compact('person'));
    }

    // ---------------------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------------------

    public function update(Request $request, Person $person)
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot edit records.');

        $validated = $request->validate(
            Person::validationRules(true, $person->id),
            Person::validationMessages()
        );

        // Handle headshot from media picker / removal
        if ($request->filled('headshot_media_id')) {
            $media = Media::findOrFail($validated['headshot_media_id']);
            $validated['headshot'] = $media->path;
        } elseif ($request->input('headshot_remove') === '1') {
            $validated['headshot'] = null;
        } else {
            unset($validated['headshot']);
        }

        // Handle CV from media picker / removal
        if ($request->filled('cv_media_id')) {
            $media = Media::findOrFail($validated['cv_media_id']);
            $validated['cv_file'] = $media->path;
        } elseif ($request->input('cv_remove') === '1') {
            $validated['cv_file'] = null;
        } else {
            unset($validated['cv_file']);
        }

        // Remove picker inputs from validated data
        unset($validated['headshot_media_id'], $validated['headshot_remove'],
              $validated['cv_media_id'], $validated['cv_remove']);

        $person->update($validated);

        return redirect()->route('persons.index')
            ->with('success', 'Member updated successfully.');
    }

    // ---------------------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------------------

    public function destroy(Person $person)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can delete records.');
        }

        // Delete uploaded files
        if ($person->headshot) {
            Storage::disk('public')->delete($person->headshot);
        }
        if ($person->cv_file) {
            Storage::disk('public')->delete($person->cv_file);
        }

        $person->forceDelete();

        return redirect()->route('persons.index')
            ->with('success', 'Record deleted successfully.');
    }

    // ---------------------------------------------------------------------------
    // Bulk delete
    // ---------------------------------------------------------------------------

    public function bulkDestroy(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can delete records.');
        }

        $request->validate([
            'ids'   => 'required|array|min:1|max:500',
            'ids.*' => 'required|integer|exists:persons,id',
        ]);

        $persons = Person::whereIn('id', $request->ids)->get();
        foreach ($persons as $p) {
            if ($p->headshot) Storage::disk('public')->delete($p->headshot);
            if ($p->cv_file)  Storage::disk('public')->delete($p->cv_file);
        }

        Person::whereIn('id', $request->ids)->forceDelete();

        return redirect()->route('persons.index')
            ->with('success', count($request->ids) . ' records deleted successfully.');
    }

    // ---------------------------------------------------------------------------
    // Import CSV
    // ---------------------------------------------------------------------------

    public function importForm()
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot import records.');
        return view('persons.import');
    }

    public function importCsv(Request $request)
    {
        if (auth()->user()->isViewer()) abort(403, 'Viewers cannot import records.');
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:51200',
        ]);

        $file   = $request->file('csv_file');
        $path   = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            return back()->withErrors(['csv_file' => 'The file could not be read.']);
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'The file is empty or has an invalid format.']);
        }

        $header = array_map(fn($h) => strtolower(trim(str_replace(' ', '_', $h))), $header);

        $columnMap = [
            'first_name'           => ['first_name', 'name', 'Ù†Ø§Ù…'],
            'last_name'            => ['last_name', 'lastname', 'Ù†Ø§Ù…_Ø®Ø§Ù†ÙˆØ§Ø¯Ú¯ÛŒ'],
            'date_of_birth'        => ['date_of_birth', 'dob', 'birth_date', 'ØªØ§Ø±ÛŒØ®_ØªÙˆÙ„Ø¯'],
            'occupation'           => ['occupation', 'current_occupation', 'organization', 'Ø´ØºÙ„'],
            'email'                => ['email', 'email_address', 'Ø§ÛŒÙ…ÛŒÙ„'],
            'waen_email'           => ['waen_email', 'waen_email_address'],
            'whatsapp'             => ['whatsapp', 'whatsapp_number', 'ÙˆØ§ØªØ³Ø§Ù¾'],
            'phone'                => ['phone', 'phone_number', 'ØªÙ„ÙÙ†'],
            'street_address'       => ['street_address', 'address', 'Ø¢Ø¯Ø±Ø³'],
            'apartment'            => ['apartment', 'apt', 'suite'],
            'city'                 => ['city', 'Ø´Ù‡Ø±'],
            'state_province'       => ['state_province', 'state', 'province', 'Ø§Ø³ØªØ§Ù†'],
            'zip_code'             => ['zip_code', 'zip', 'postal_code', 'Ú©Ø¯_Ù¾Ø³ØªÛŒ'],
            'country'              => ['country', 'Ú©Ø´ÙˆØ±'],
            'facebook'             => ['facebook', 'facebook_url'],
            'instagram'            => ['instagram', 'instagram_url'],
            'linkedin'             => ['linkedin', 'linkedin_url'],
            'twitter'              => ['twitter', 'x', 'twitter_url'],
            'education'            => ['education', 'تحصیلات'],
            'gender'               => ['gender', 'جنسیت'],
            'biography'            => ['biography', 'bio', 'Ø¨ÛŒÙˆÚ¯Ø±Ø§ÙÛŒ'],
            'areas_of_expertise'   => ['areas_of_expertise', 'expertise', 'ØªØ®ØµØµ'],
            'proposed_initiatives' => ['proposed_initiatives', 'initiatives', 'Ø§Ø¨ØªÚ©Ø§Ø±Ø§Øª'],
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

        $existingEmails = DB::table('persons')
            ->whereNotNull('email')->where('email', '!=', '')
            ->pluck('email')
            ->flip()
            ->all();
        $seenEmailsThisImport = [];

        $batch     = [];
        $batchSize = 500;
        $imported  = 0;
        $skipped   = 0;
        $errors    = 0;
        $now       = now();

        while (($row = fgetcsv($handle)) !== false) {
            $g = fn(string $f, int $max = 100) =>
                isset($colIndex[$f]) ? substr(trim($row[$colIndex[$f]] ?? ''), 0, $max) : null;

            $record = [
                'first_name'           => $g('first_name') ?? '',
                'last_name'            => $g('last_name') ?? '',
                'date_of_birth'        => $g('date_of_birth', 10) ?: null,
                'occupation'           => $g('occupation', 200),
                'email'                => isset($colIndex['email'])
                    ? substr(strtolower(trim($row[$colIndex['email']] ?? '')), 0, 191) : null,
                'waen_email'           => isset($colIndex['waen_email'])
                    ? substr(strtolower(trim($row[$colIndex['waen_email']] ?? '')), 0, 191) : null,
                'whatsapp'             => $g('whatsapp', 30),
                'phone'                => $g('phone', 30),
                'street_address'       => $g('street_address', 255),
                'apartment'            => $g('apartment'),
                'city'                 => $g('city'),
                'state_province'       => $g('state_province'),
                'zip_code'             => $g('zip_code', 20),
                'country'              => $g('country'),
                'facebook'             => $g('facebook', 255),
                'instagram'            => $g('instagram', 255),
                'linkedin'             => $g('linkedin', 255),
                'twitter'              => $g('twitter', 255),
                'education'            => $g('education'),
                'gender'               => $g('gender', 20),
                'biography'            => isset($colIndex['biography'])
                    ? substr(trim($row[$colIndex['biography']] ?? ''), 0, 5000) : null,
                'areas_of_expertise'   => isset($colIndex['areas_of_expertise'])
                    ? substr(trim($row[$colIndex['areas_of_expertise']] ?? ''), 0, 5000) : null,
                'proposed_initiatives' => isset($colIndex['proposed_initiatives'])
                    ? substr(trim($row[$colIndex['proposed_initiatives']] ?? ''), 0, 5000) : null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];

            if (empty($record['first_name'])) {
                $errors++;
                continue;
            }

            // Sanitize empty strings to null
            foreach (array_keys($record) as $f) {
                if ($f === 'first_name' || $f === 'last_name' || $f === 'created_at' || $f === 'updated_at') continue;
                if ($record[$f] === '') $record[$f] = null;
            }

            if ($record['email'] && !filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                $record['email'] = null;
            }

            if (!empty($record['email'])) {
                if (isset($existingEmails[$record['email']]) || isset($seenEmailsThisImport[$record['email']])) {
                    $skipped++;
                    continue;
                }
                $seenEmailsThisImport[$record['email']] = true;
            }

            $batch[] = $record;

            if (count($batch) >= $batchSize) {
                DB::table('persons')->insertOrIgnore($batch);
                $imported += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('persons')->insertOrIgnore($batch);
            $imported += count($batch);
        }

        fclose($handle);

        $message = "Import completed. {$imported} records imported.";
        if ($skipped > 0) $message .= " {$skipped} rows skipped (duplicate email).";
        if ($errors > 0)  $message .= " {$errors} rows skipped (missing name).";

        return redirect()->route('persons.index')->with('success', $message);
    }

    // ---------------------------------------------------------------------------
    // Export
    // ---------------------------------------------------------------------------

    public function exportForm(Request $request)
    {
        $pluck = fn($col) => Person::select($col)
            ->whereNotNull($col)->where($col, '!=', '')
            ->distinct()->orderBy($col)->pluck($col);

        $countries = $pluck('country');
        $cities    = $pluck('city');
        $states    = $pluck('state_province');

        return view('persons.export', compact('countries', 'cities', 'states'));
    }

    // ---------------------------------------------------------------------------
    // Export CSV download
    // ---------------------------------------------------------------------------

    public function exportCsv(Request $request)
    {
        $request->validate([
            'search'  => 'nullable|string|max:200',
            'country' => 'nullable|string|max:100',
            'city'    => 'nullable|string|max:100',
            'state'   => 'nullable|string|max:100',
        ]);

        $search  = $request->input('search');
        $country = $request->input('country');
        $city    = $request->input('city');
        $state   = $request->input('state');

        $filename = 'waen_members_export_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($search, $country, $city, $state) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID', 'First Name', 'Last Name', 'Date of Birth', 'Occupation',
                'Email', 'WAEN Email', 'WhatsApp', 'Phone',
                'Street Address', 'Apartment', 'City', 'State/Province', 'ZIP Code', 'Country',
                'Education', 'Gender',
                'Facebook', 'Instagram', 'LinkedIn', 'X (Twitter)',
                'Biography', 'Areas of Expertise', 'Proposed Initiatives',
            ]);

            Person::query()
                ->search($search)
                ->byCountry($country)
                ->byCity($city)
                ->byState($state)
                ->orderBy('id')
                ->chunk(1000, function ($persons) use ($handle) {
                    foreach ($persons as $p) {
                        fputcsv($handle, [
                            $p->id, $p->first_name, $p->last_name,
                            $p->date_of_birth?->format('Y-m-d'), $p->occupation,
                            $p->email, $p->waen_email, $p->whatsapp, $p->phone,
                            $p->street_address, $p->apartment, $p->city, $p->state_province, $p->zip_code, $p->country,
                            $p->education, $p->gender,
                            $p->facebook, $p->instagram, $p->linkedin, $p->twitter,
                            $p->biography, $p->areas_of_expertise, $p->proposed_initiatives,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ---------------------------------------------------------------------------
    // Sample CSV
    // ---------------------------------------------------------------------------

    public function sampleCsv()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="waen_members_sample_import.csv"',
            'Cache-Control'       => 'no-cache',
        ];

        $rows = [
            ['first_name', 'last_name', 'date_of_birth', 'occupation', 'email', 'waen_email', 'whatsapp', 'phone', 'street_address', 'apartment', 'city', 'state_province', 'zip_code', 'country', 'education', 'gender', 'facebook', 'instagram', 'linkedin', 'twitter', 'biography', 'areas_of_expertise', 'proposed_initiatives'],
            ['Ahmad', 'Rahimi', '1990-05-15', 'Software Engineer at TechCorp', 'ahmad@example.com', 'ahmad@waen.org', '+93700123456', '+1234567890', '123 Main St', 'Apt 4B', 'Kabul', 'Kabul', '1001', 'Afghanistan', 'Master\'s Degree', 'Male', 'https://facebook.com/ahmad', 'https://instagram.com/ahmad', 'https://linkedin.com/in/ahmad', 'https://x.com/ahmad', 'Experienced engineer...', 'Machine Learning, Data Science', 'AI for Education'],
            ['Sara', 'Karimi', '1988-11-22', 'Professor at University', 'sara@example.com', '', '+93799654321', '', '456 Oak Ave', '', 'Herat', 'Herat', '2001', 'Afghanistan', 'PhD', 'Female', '', '', 'https://linkedin.com/in/sara', '', 'Academic researcher...', 'Public Health, Policy', 'Health Education Programs'],
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
}