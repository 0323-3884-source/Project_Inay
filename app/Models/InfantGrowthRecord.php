<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfantGrowthRecord extends Model
{
    protected $fillable = [
        'infant_id',
        'recorded_by_staff_id',
        'measured_at',
        'age_months',
        'weight',
        'height',
        'head_circumference',
        'temperature',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'age_months' => 'integer',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'head_circumference' => 'decimal:2',
            'temperature' => 'decimal:1',
        ];
    }

    public function infant(): BelongsTo
    {
        return $this->belongsTo(Infant::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'recorded_by_staff_id');
    }
}
