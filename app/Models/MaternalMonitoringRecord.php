<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaternalMonitoringRecord extends Model
{
    protected $fillable = [
        'mother_id',
        'staff_mother_casefile_id',
        'recorded_by_staff_id',
        'pregnancy_week',
        'pregnancy_month',
        'bp_systolic',
        'bp_diastolic',
        'blood_sugar',
        'weight',
        'hemoglobin',
        'temperature',
        'heart_rate',
        'risk_level',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'pregnancy_week' => 'integer',
            'pregnancy_month' => 'integer',
            'bp_systolic' => 'integer',
            'bp_diastolic' => 'integer',
            'blood_sugar' => 'decimal:1',
            'weight' => 'decimal:2',
            'hemoglobin' => 'decimal:1',
            'temperature' => 'decimal:1',
            'heart_rate' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Mother::class);
    }

    public function casefile(): BelongsTo
    {
        return $this->belongsTo(StaffMotherCasefile::class, 'staff_mother_casefile_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'recorded_by_staff_id');
    }
}
