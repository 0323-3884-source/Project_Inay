<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffCoordinationThread extends Model
{
    protected $fillable = [
        'staff_one_id',
        'staff_two_id',
        'last_message_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function staffOne(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'staff_one_id');
    }

    public function staffTwo(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'staff_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StaffCoordinationMessage::class, 'thread_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(StaffCoordinationMessage::class, 'last_message_id');
    }
}
