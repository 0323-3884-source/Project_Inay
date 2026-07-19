<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminStaffThread extends Model
{
    protected $fillable = [
        'admin_user_id',
        'program_staff_id',
        'last_message_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    public function programStaff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'program_staff_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AdminStaffMessage::class, 'thread_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(AdminStaffMessage::class, 'last_message_id');
    }
}
