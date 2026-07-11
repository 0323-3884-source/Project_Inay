<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Infant extends Model
{
    protected $fillable = [
        'mother_id',
        'full_name',
        'sex',
        'birth_date',
        'birth_weight',
        'birth_height',
        'facility',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'birth_weight' => 'decimal:2',
            'birth_height' => 'decimal:2',
        ];
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Mother::class);
    }

    public function growthRecords(): HasMany
    {
        return $this->hasMany(InfantGrowthRecord::class)->orderBy('measured_at')->orderBy('created_at');
    }

    public function vaccineRecords(): HasMany
    {
        return $this->hasMany(InfantVaccineRecord::class)->orderBy('due_date')->orderBy('id');
    }
}
