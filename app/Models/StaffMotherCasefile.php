<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMotherCasefile extends Model
{
    protected $fillable = [
        'staff_id',
        'mother_id',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ProgramStaff::class, 'staff_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Mother::class);
    }
}
