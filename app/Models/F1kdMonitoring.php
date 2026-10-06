<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\F1kdCompliance;

class F1kdMonitoring extends Model
{
    protected $guarded = ['id'];

    public function recordedByStaff(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'recorded_by_staff_id');
    }

    public function verifiedByDswdStaff(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DswdStaff::class, 'verified_by_dswd_staff_id');
    }

    public function getMonthlyStatusAttribute(): string
    {
        return F1kdCompliance::attendanceStatus($this->attendance_status);
    }

    protected function casts(): array
    {
        return ['reporting_month' => 'date', 'checklist' => 'array', 'dswd_verified_at'=>'datetime'];
    }
}
