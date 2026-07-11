<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InayKaalamanUpload extends Model
{
    protected $fillable = [
        'mother_id',
        'month',
        'record_type',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'size' => 'integer',
        ];
    }
}
