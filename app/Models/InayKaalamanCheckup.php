<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InayKaalamanCheckup extends Model
{
    protected $fillable = [
        'mother_id',
        'month',
        'checkup_date',
        'healthcare_worker_name',
        'facility_name',
        'notes',
        'recorded_by_staff_id',
        'recorded_at',
        'verified_by_staff_id',
        'verified_at',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'checkup_date' => 'date',
            'recorded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Mother::class);
    }

    public function verifiedByStaff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'verified_by_staff_id');
    }

    public function recordedByStaff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'recorded_by_staff_id');
    }
}
