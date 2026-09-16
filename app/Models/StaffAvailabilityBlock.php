<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAvailabilityBlock extends Model
{
    protected $fillable = [
        'staff_id',
        'blocked_date',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'blocked_date' => 'date',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'staff_id');
    }
}
