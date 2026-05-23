<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class PublicCertificateVerificationController extends Controller
{
    public function index(Request $request)
    {
        $certificateNumber = trim((string) $request->input('certificate_number'));
        $certificate = null;
        $searched = $certificateNumber !== '';

        if ($searched) {
            $certificate = Certificate::with('person')
                ->where('certificate_number', $certificateNumber)
                ->first();
        }

        return view('verify.index', compact('certificate', 'certificateNumber', 'searched'));
    }

    public function byToken(string $token)
    {
        $certificate = Certificate::with('person')
            ->where('verify_token', $token)
            ->firstOrFail();

        $certificateNumber = $certificate->certificate_number;
        $searched = false;

        return view('verify.index', compact('certificate', 'certificateNumber', 'searched'));
    }
}
