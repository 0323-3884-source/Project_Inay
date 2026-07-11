<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfantVaccineRecord extends Model
{
    protected $fillable = [
        'infant_id',
        'recorded_by_staff_id',
        'vaccine_group',
        'vaccine_name',
        'dose_label',
        'due_date',
        'status',
        'administered_at',
        'facility',
        'lot_number',
        'vaccinator',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'administered_at' => 'date',
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
