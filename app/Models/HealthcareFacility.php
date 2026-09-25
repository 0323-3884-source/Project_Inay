<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthcareFacility extends Model
{
    protected $fillable = ['name', 'barangay', 'identity_key'];

    public static function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
    }

    public static function forDetails(?string $name, ?string $barangay): ?self
    {
        if (trim((string) $name) === '') {
            return null;
        }

        return self::firstOrCreate([
            'identity_key' => hash('sha256', self::normalize($name).'|'.self::normalize((string) $barangay)),
        ], ['name' => trim($name), 'barangay' => trim((string) $barangay) ?: null]);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(ProgramStaff::class);
    }

    public function midwives(): HasMany
    {
        return $this->hasMany(MidwifeProfile::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
