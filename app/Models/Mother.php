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
        'contact_number',
        'age',
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

    public function maternalMonitoringRecords(): HasMany
    {
        return $this->hasMany(MaternalMonitoringRecord::class);
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

