<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F1kdMonitoring extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reporting_month' => 'date', 'checklist' => 'array'];
    }
}
