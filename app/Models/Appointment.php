<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_RESCHEDULED = 'rescheduled';
    public const STATUS_RESCHEDULE_REQUESTED = 'reschedule_requested';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MISSED = 'missed';

    public const MEETING_IN_PERSON = 'in_person';
    public const MEETING_VIDEO = 'video_consultation';
    public const MEETING_PHONE = 'phone_call';
    public const MEETING_CHAT = 'chat';

    protected $fillable = [
        'healthcare_facility_id',
        'midwife_profile_id',
        'care_team_snapshot',
        'mother_id',
        'staff_id',
        'staff_availability_id',
        'conversation_id',
        'appointment_type',
        'meeting_type',
        'appointment_date',
        'start_time',
        'end_time',
        'location',
        'notes',
        'status',
        'decline_reason',
        'reschedule_reason',
        'preferred_date',
        'preferred_start_time',
        'created_by_id',
        'created_by_role',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'care_team_snapshot' => 'array',
            'appointment_date' => 'date',
            'preferred_date' => 'date',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function appointmentTypeLabels(): array
    {
        return [
            'prenatal_checkup' => 'Prenatal Checkup',
            'postnatal_checkup' => 'Postnatal Checkup',
            'child_checkup' => 'Child Checkup',
            'vaccination' => 'Vaccination',
            'follow_up_consultation' => 'Follow-up Consultation',
            'video_consultation' => 'Video Consultation',
            'other' => 'Other',
        ];
    }

    public static function meetingTypeLabels(): array
    {
        return [
            self::MEETING_IN_PERSON => 'Face-to-face',
            self::MEETING_VIDEO => 'Video call',
            self::MEETING_PHONE => 'Phone call',
            self::MEETING_CHAT => 'Chat',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_RESCHEDULED => 'Rescheduled',
            self::STATUS_RESCHEDULE_REQUESTED => 'Reschedule Requested',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_MISSED => 'Missed',
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_RESCHEDULED,
            self::STATUS_RESCHEDULE_REQUESTED,
        ];
    }

    public static function terminalStatuses(): array
    {
        return [
            self::STATUS_DECLINED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
            self::STATUS_MISSED,
        ];
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Mother::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HealthcareFacility::class, 'healthcare_facility_id');
    }

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(MidwifeProfile::class, 'midwife_profile_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'staff_id');
    }

    public function staffAvailability(): BelongsTo
    {
        return $this->belongsTo(StaffAvailability::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function typeLabel(): string
    {
        return self::appointmentTypeLabels()[$this->appointment_type] ?? 'Appointment';
    }

    public function meetingLabel(): string
    {
        return self::meetingTypeLabels()[$this->meeting_type] ?? 'Meeting';
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
