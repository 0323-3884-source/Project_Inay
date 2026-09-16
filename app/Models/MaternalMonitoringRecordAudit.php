<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaternalMonitoringRecordAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'maternal_monitoring_record_id',
        'mother_id',
        'staff_id',
        'action',
        'before_values',
        'after_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaternalMonitoringRecord::class, 'maternal_monitoring_record_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Mother::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class);
    }
}
