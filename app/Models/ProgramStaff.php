<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProgramStaff extends Model
{
    protected $table = 'program_staff';

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'staff_id',
        'position',
        'role',
        'contact_number',
        'healthcare_worker_id_photo_path',
        'healthcare_worker_id_verified_at',
        'healthcare_worker_id_verified_by_admin_id',
        'approval_status',
        'approved_at',
        'approved_by_admin_id',
        'rejected_at',
        'rejection_reason',
        'assigned_barangay',
        'assigned_facility',
        'accepting_appointments',
        'max_appointments_per_day',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'healthcare_worker_id_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'accepting_appointments' => 'boolean',
        'max_appointments_per_day' => 'integer',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role ?: ($this->position ?: 'Program Staff');
    }

    public function setHealthcareWorkerIdPhotoPathAttribute(?string $value): void
    {
        $this->attributes['healthcare_worker_id_photo_path'] = $this->normalizeHealthcareWorkerIdPhotoPath($value);
    }

    public function getHealthcareWorkerIdPhotoUrlAttribute(): ?string
    {
        $path = $this->healthcareWorkerIdPhotoStoragePath();

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    public function getHasHealthcareWorkerIdPhotoAttribute(): bool
    {
        $path = $this->healthcareWorkerIdPhotoStoragePath();

        return $path !== null && Storage::disk('public')->exists($path);
    }

    public function healthcareWorkerIdPhotoStoragePath(): ?string
    {
        return $this->normalizeHealthcareWorkerIdPhotoPath($this->healthcare_worker_id_photo_path);
    }

    public function getIsHealthcareWorkerIdVerifiedAttribute(): bool
    {
        return $this->healthcare_worker_id_verified_at !== null;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Pending Approval',
        };
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'healthcare_worker_id_verified_by_admin_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'approved_by_admin_id');
    }

    public function casefileMothers(): BelongsToMany
    {
        return $this->belongsToMany(Mother::class, 'staff_mother_casefiles', 'staff_id', 'mother_id')
            ->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(StaffAvailability::class, 'staff_id');
    }

    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(StaffAvailabilityBlock::class, 'staff_id');
    }

    public function coordinationMessagesSent(): HasMany
    {
        return $this->hasMany(StaffCoordinationMessage::class, 'sender_staff_id');
    }

    public function coordinationMessagesReceived(): HasMany
    {
        return $this->hasMany(StaffCoordinationMessage::class, 'receiver_staff_id');
    }

    public function verifiedInayKaalamanCheckups(): HasMany
    {
        return $this->hasMany(InayKaalamanCheckup::class, 'verified_by_staff_id');
    }

    public function recordedInayKaalamanCheckups(): HasMany
    {
        return $this->hasMany(InayKaalamanCheckup::class, 'recorded_by_staff_id');
    }

    private function normalizeHealthcareWorkerIdPhotoPath(?string $value): ?string
    {
        $path = trim((string) $value);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($urlPath) ? $urlPath : $path;
        }

        $path = str_replace('\\', '/', $path);
        $publicDiskRoot = rtrim(str_replace('\\', '/', Storage::disk('public')->path('')), '/').'/';
        $publicLinkRoot = rtrim(str_replace('\\', '/', public_path('storage')), '/').'/';

        foreach ([$publicDiskRoot, $publicLinkRoot] as $root) {
            if ($root !== '/' && str_starts_with($path, $root)) {
                $path = substr($path, strlen($root));
                break;
            }
        }

        $path = ltrim($path, '/');

        foreach (['storage/app/public/', 'public/storage/', 'storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        $path = ltrim($path, '/');

        if (
            $path === ''
            || preg_match('/(^|\/)\.\.(\/|$)/', $path)
            || preg_match('/^[A-Za-z]:\//', $path)
        ) {
            return null;
        }

        return $path;
    }
}
