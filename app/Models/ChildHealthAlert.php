<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildHealthAlert extends Model
{
    protected $fillable = [
        'infant_id',
        'created_by_staff_id',
        'alert_type',
        'title',
        'notes',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function infant(): BelongsTo
    {
        return $this->belongsTo(Infant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'created_by_staff_id');
    }
}
