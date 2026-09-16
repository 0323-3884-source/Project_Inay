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
        'blood_sugar_test_type',
        'weight',
        'height_cm',
        'pre_pregnancy_weight',
        'pre_pregnancy_bmi',
        'weight_change_from_previous',
        'hemoglobin',
        'temperature',
        'heart_rate',
        'bp_status',
        'blood_sugar_status',
        'hemoglobin_status',
        'weight_status',
        'temperature_status',
        'heart_rate_status',
        'screening_summary_status',
        'measurement_units',
        'screening_explanations',
        'screening_guidelines',
        'confirmed_unusual_at',
        'confirmed_unusual_by_staff_id',
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
            'blood_sugar_test_type' => 'string',
            'weight' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'pre_pregnancy_weight' => 'decimal:2',
            'pre_pregnancy_bmi' => 'decimal:2',
            'weight_change_from_previous' => 'decimal:2',
            'hemoglobin' => 'decimal:1',
            'temperature' => 'decimal:1',
            'heart_rate' => 'integer',
            'measurement_units' => 'array',
            'screening_explanations' => 'array',
            'screening_guidelines' => 'array',
            'confirmed_unusual_at' => 'datetime',
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

    public function unusualConfirmer(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'confirmed_unusual_by_staff_id');
    }
}
