<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MidwifeProfile extends Model
{
    protected $fillable = [
        'program_staff_id', 'healthcare_facility_id', 'created_by_staff_id',
        'full_name', 'identity_key', 'contact_number', 'availability_status',
    ];

    public function programStaff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HealthcareFacility::class, 'healthcare_facility_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function assignedWorkers(): HasMany
    {
        return $this->hasMany(ProgramStaff::class, 'assigned_midwife_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->programStaff?->full_name ?? $this->full_name;
    }

    public function getIsAvailableAttribute(): bool
    {
        if ($this->programStaff) {
            return $this->programStaff->is_approved
                && $this->programStaff->is_midwife
                && ($this->programStaff->accepting_appointments ?? true);
        }

        return $this->availability_status === 'available';
    }

    public function currentFacility(): ?HealthcareFacility
    {
        return $this->program_staff_id ? $this->programStaff?->facility : $this->facility;
    }
}
