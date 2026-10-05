<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\F1kdCompliance;

class F1kdMonitoring extends Model
{
    protected $guarded = ['id'];

    public function getMonthlyStatusAttribute(): string
    {
        return F1kdCompliance::attendanceStatus($this->attendance_status);
    }

    protected function casts(): array
    {
        return ['reporting_month' => 'date', 'checklist' => 'array'];
    }
}
