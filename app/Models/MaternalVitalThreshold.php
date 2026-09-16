<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaternalVitalThreshold extends Model
{
    protected $fillable = [
        'key',
        'measurement',
        'test_type',
        'threshold_type',
        'label',
        'value',
        'unit',
        'guideline_name',
        'guideline_version',
        'source_url',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
