<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'persons';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'occupation',
        'email',
        'waen_email',
        'whatsapp',
        'phone',
        'street_address',
        'apartment',
        'city',
        'state_province',
        'zip_code',
        'country',
        'education',
        'gender',
        'facebook',
        'instagram',
        'linkedin',
        'twitter',
        'biography',
        'headshot',
        'cv_file',
        'areas_of_expertise',
        'proposed_initiatives',
    ];

    protected $hidden = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    // ---------------------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------------------

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getHeadshotUrlAttribute(): ?string
    {
        if (!$this->headshot) {
            return null;
        }
        return asset('storage/' . $this->headshot);
    }

    public function getCvUrlAttribute(): ?string
    {
        if (!$this->cv_file) {
            return null;
        }
        return asset('storage/' . $this->cv_file);
    }

    // ---------------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------------

    public function scopeSearch($query, ?string $term)
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('first_name',    'LIKE', $like)
              ->orWhere('last_name',    'LIKE', $like)
              ->orWhere('email',        'LIKE', $like)
              ->orWhere('waen_email',   'LIKE', $like)
              ->orWhere('phone',        'LIKE', $like)
              ->orWhere('whatsapp',     'LIKE', $like)
              ->orWhere('city',         'LIKE', $like)
              ->orWhere('state_province','LIKE', $like)
              ->orWhere('country',      'LIKE', $like)
              ->orWhere('occupation',   'LIKE', $like)
              ->orWhere('biography',    'LIKE', $like)
              ->orWhere('areas_of_expertise', 'LIKE', $like);
        });
    }

    public function scopeByCountry($query, ?string $country)
    {
        if ($country === null || $country === '') {
            return $query;
        }
        return $query->where('country', $country);
    }

    public function scopeByCity($query, ?string $city)
    {
        if ($city === null || $city === '') {
            return $query;
        }
        return $query->where('city', $city);
    }

    public function scopeByState($query, ?string $state)
    {
        if ($state === null || $state === '') {
            return $query;
        }
        return $query->where('state_province', $state);
    }

    // ---------------------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------------------

    public static function validationRules(bool $isUpdate = false, ?int $id = null): array
    {
        $emailRule     = 'nullable|email:rfc|max:191|unique:persons,email';
        $waenEmailRule = 'nullable|email:rfc|max:191|unique:persons,waen_email';

        if ($isUpdate && $id) {
            $emailRule     .= ',' . $id;
            $waenEmailRule .= ',' . $id;
        }

        return [
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'required|string|max:100',
            'date_of_birth'        => 'nullable|date|before:today',
            'occupation'           => 'nullable|string|max:200',
            'email'                => $emailRule,
            'waen_email'           => $waenEmailRule,
            'whatsapp'             => 'nullable|string|max:30|regex:/^[\d\s\+\-\(\)]+$/',
            'phone'                => 'nullable|string|max:30|regex:/^[\d\s\+\-\(\)]+$/',
            'street_address'       => 'nullable|string|max:255',
            'apartment'            => 'nullable|string|max:100',
            'city'                 => 'nullable|string|max:100',
            'state_province'       => 'nullable|string|max:100',
            'zip_code'             => 'nullable|string|max:20',
            'country'              => 'nullable|string|max:100',
            'education'            => 'nullable|string|max:100',
            'gender'               => 'nullable|string|in:Male,Female,Other',
            'facebook'             => 'nullable|url|max:255',
            'instagram'            => 'nullable|url|max:255',
            'linkedin'             => 'nullable|url|max:255',
            'twitter'              => 'nullable|url|max:255',
            'biography'            => 'nullable|string|max:5000',
            'headshot_media_id'    => 'nullable|integer|exists:media,id',
            'headshot_remove'      => 'nullable|string',
            'cv_media_id'          => 'nullable|integer|exists:media,id',
            'cv_remove'            => 'nullable|string',
            'areas_of_expertise'   => 'nullable|string|max:5000',
            'proposed_initiatives' => 'nullable|string|max:5000',
        ];
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->orderByDesc('issued_at')->orderByDesc('id');
    }

    public static function validationMessages(): array
    {
        return [
            'first_name.required'    => 'First name is required.',
            'first_name.max'         => 'First name must not exceed 100 characters.',
            'last_name.required'     => 'Last name is required.',
            'last_name.max'          => 'Last name must not exceed 100 characters.',
            'date_of_birth.date'     => 'Please provide a valid date.',
            'date_of_birth.before'   => 'Date of birth must be in the past.',
            'email.email'            => 'Please provide a valid email address.',
            'email.unique'           => 'This email address is already registered.',
            'waen_email.email'       => 'Please provide a valid WAEN email address.',
            'waen_email.unique'      => 'This WAEN email is already registered.',
            'phone.regex'            => 'Phone number may only contain digits, spaces, +, - and ().',
            'whatsapp.regex'         => 'WhatsApp number may only contain digits, spaces, +, - and ().',
            'headshot_media_id.exists' => 'The selected headshot image was not found in the media library.',
            'cv_media_id.exists'       => 'The selected CV file was not found in the media library.',
            'facebook.url'           => 'Facebook must be a valid URL.',
            'instagram.url'          => 'Instagram must be a valid URL.',
            'linkedin.url'           => 'LinkedIn must be a valid URL.',
            'twitter.url'            => 'X (Twitter) must be a valid URL.',
        ];
    }
}
