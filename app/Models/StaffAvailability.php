<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class StaffAvailability extends Model
{
    protected $fillable = [
        'staff_id',
        'day_of_week',
        'start_time',
        'end_time',
        'appointment_type',
        'meeting_type',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function dayLabels(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'staff_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_availability_id');
    }

    public function dayLabel(): string
    {
        return self::dayLabels()[$this->day_of_week] ?? 'Available day';
    }

    public function typeLabel(): string
    {
        return Appointment::appointmentTypeLabels()[$this->appointment_type] ?? 'Consultation';
    }

    public function meetingLabel(): string
    {
        return Appointment::meetingTypeLabels()[$this->meeting_type] ?? 'Meeting';
    }

    public function timeLabel(): string
    {
        return Carbon::parse((string) $this->start_time)->format('g:i A')
            .' - '
            .Carbon::parse((string) $this->end_time)->format('g:i A');
    }
}
