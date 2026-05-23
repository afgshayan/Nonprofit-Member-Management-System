<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = [
        'person_id',
        'certificate_number',
        'verify_token',
        'title',
        'issued_at',
        'notes',
        'pdf_media_id',
        'issued_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function pdfMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'pdf_media_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getVerifyUrlAttribute(): string
    {
        return route('verify.token', $this->verify_token);
    }

    public function getQrDownloadUrlAttribute(): string
    {
        return route('certificates.qr', $this);
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(12);
        } while (self::where('verify_token', $token)->exists());

        return $token;
    }

    public static function generateCertificateNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'CERT-' . $year . '-';

        $latest = self::where('certificate_number', 'like', $prefix . '%')
            ->orderByDesc('certificate_number')
            ->value('certificate_number');

        $next = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (self::where('certificate_number', $number)->exists());

        return $number;
    }
}
