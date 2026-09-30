<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mother extends Model
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'barangay',
        'municipality_city',
        'contact_number',
        'age',
        'civil_status',
        'gravidity',
        'parity',
        'blood_type',
        'pregnancy_status',
        'profile_photo_path',
        'location_latitude',
        'location_longitude',
        'location_accuracy',
        'is_4ps_beneficiary',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'gravidity' => 'integer',
            'parity' => 'integer',
            'location_latitude' => 'decimal:7',
            'location_longitude' => 'decimal:7',
            'location_accuracy' => 'integer',
            'is_4ps_beneficiary' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function getMaternalAgeRiskAttribute(): ?string
    {
        if ($this->age === null) {
            return null;
        }

        return match (true) {
            $this->age < 18 => 'Young Maternal Age Risk',
            $this->age >= 35 => 'Advanced Maternal Age Risk',
            default => 'Standard Maternal Age',
        };
    }

    public function maternalMonitoringRecords(): HasMany
    {
        return $this->hasMany(MaternalMonitoringRecord::class);
    }

    public function inayKaalamanProgress(): HasMany
    {
        return $this->hasMany(InayKaalamanProgress::class);
    }

    public function inayKaalamanUploads(): HasMany
    {
        return $this->hasMany(InayKaalamanUpload::class);
    }

    public function inayKaalamanCheckups(): HasMany
    {
        return $this->hasMany(InayKaalamanCheckup::class);
    }

    public function casefileStaff(): BelongsToMany
    {
        return $this->belongsToMany(ProgramStaff::class, 'staff_mother_casefiles', 'mother_id', 'staff_id')
            ->withTimestamps();
    }

    public function infants(): HasMany
    {
        return $this->hasMany(Infant::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

