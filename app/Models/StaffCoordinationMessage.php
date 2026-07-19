<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCoordinationMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_staff_id',
        'receiver_staff_id',
        'message',
        'is_read',
        'read_at',
        'is_unsent',
        'unsent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'is_unsent' => 'boolean',
            'unsent_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(StaffCoordinationThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'sender_staff_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'receiver_staff_id');
    }
}
