<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminStaffMessage extends Model
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PROGRAM_STAFF = 'program_staff';

    protected $fillable = [
        'thread_id',
        'sender_role',
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
        return $this->belongsTo(AdminStaffThread::class, 'thread_id');
    }
}
