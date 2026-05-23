<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                linear-gradient(rgba(15, 23, 42, .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, .05) 1px, transparent 1px),
                linear-gradient(180deg, #f8fafc, #eef2f7);
            background-size: 48px 48px, 48px 48px, auto;
            color: #0f172a;
            min-height: 100vh;
        }
        .verify-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 20px;
        }
        .verify-frame {
            width: 100%;
            max-width: 1040px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 24px 80px rgba(15, 23, 42, .08);
            border: 1px solid rgba(148, 163, 184, .18);
        }
        .verify-main {
            padding: 56px;
        }
        .eyebrow {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #ea580c;
            margin-bottom: 18px;
        }
        .headline {
            font-size: 56px;
            line-height: .95;
            font-weight: 800;
            margin: 0 0 20px;
            color: #0f172a;
        }
        .headline span {
            color: #2563eb;
        }
        .lead {
            font-size: 18px;
            line-height: 1.8;
            color: #475569;
            max-width: 760px;
            margin: 0 0 34px;
        }
        .verify-form-wrap {
            background: rgba(255, 255, 255, .7);
            padding: 0;
            margin-bottom: 28px;
        }
        .form-label {
            color: #0f172a;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .verify-input {
            height: 64px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            padding: 0 18px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .verify-input:focus {
            background: #fff;
            color: #0f172a;
            border-color: #2563eb;
            box-shadow: none;
        }
        .verify-input::placeholder { color: #94a3b8; }
        .verify-btn {
            min-width: 170px;
            border: 1px solid #2563eb;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
        }
        .verify-btn:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-color: #1d4ed8;
            color: #fff;
        }
        .result-panel {
            border: 1px solid #dbeafe;
            background: linear-gradient(180deg, #eff6ff, #f8fbff);
            padding: 30px;
        }
        .result-panel.error {
            border-color: #fecaca;
            background: linear-gradient(180deg, #fef2f2, #fff7f7);
        }
        .result-status {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .18em;
            margin-bottom: 16px;
            color: #2563eb;
        }
        .result-panel.error .result-status { color: #dc2626; }
        .result-title {
            font-size: 34px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 14px;
            color: #0f172a;
        }
        .result-copy {
            color: #475569;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 22px;
        }
        .result-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 12px 18px;
            border-top: 1px solid #dbeafe;
            padding-top: 22px;
        }
        .result-panel.error .result-grid { border-top-color: #fecaca; }
        .result-grid dt {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .result-grid dd {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 600;
        }
        @media (max-width: 767.98px) {
            .verify-main { padding: 32px 22px; }
            .headline { font-size: 38px; }
            .verify-input { font-size: 20px; height: 58px; }
            .verify-btn { min-width: 120px; font-size: 13px; }
            .result-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="verify-shell">
    <div class="verify-frame">
        <section class="verify-main">
            <span class="eyebrow">Official Verification</span>
            <h1 class="headline">Verify a <span>Certificate</span></h1>
            <p class="lead">Use the issued certificate number to confirm that this credential was issued by the {{ $appName }}. This page is connected to the institution’s official records and provides an instant verification result.</p>

            <div class="verify-form-wrap">
                <form method="GET" action="{{ route('verify.index') }}">
                    <label class="form-label">Certificate Number</label>
                    <div class="input-group">
                        <input type="text" name="certificate_number" value="{{ $certificateNumber }}" class="form-control verify-input" placeholder="CERT-2026-0001">
                        <button class="btn verify-btn" type="submit">Verify</button>
                    </div>
                </form>
            </div>

            @if($certificate)
                <div class="result-panel">
                    <div class="result-status">Verified</div>
                    <div class="result-title">Certificate confirmed as authentic</div>
                    <div class="result-copy">This certificate was issued by <strong>{{ $appName }}</strong> and the official record has been matched successfully.</div>
                    <dl class="result-grid">
                        <dt>Certificate No.</dt>
                        <dd>{{ $certificate->certificate_number }}</dd>
                        <dt>Recipient</dt>
                        <dd>{{ $certificate->person->full_name }}</dd>
                        <dt>Title</dt>
                        <dd>{{ $certificate->title ?: '—' }}</dd>
                        <dt>Issued Date</dt>
                        <dd $certificate->issued_at?->format('M d, Y') ?: '—' }}</dd>
                    </dl>
                </div>
            @elseif($searched)
                <div class="result-panel error">
                    <div class="result-status">Not Found</div>
                    <div class="result-title">No matching certificate record was found</div>
                    <div class="result-copy">Please review the entered certificate number and try again. If the issue remains, contact the issuing institution for assistance.</div>
                </div>
            @endif
        </section>
    </div>
</div>
</body>
</html>
