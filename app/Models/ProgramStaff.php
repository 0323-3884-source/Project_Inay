<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'contact_number',
    ];

    protected $hidden = [
        'password',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
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
}
