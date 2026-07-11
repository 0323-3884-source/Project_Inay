<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    protected $fillable = [
        'conversation_id',
        'caller_id',
        'caller_role',
        'receiver_id',
        'receiver_role',
        'call_type',
        'status',
        'offer_type',
        'offer_sdp',
        'answer_type',
        'answer_sdp',
        'offer_payload',
        'answer_payload',
        'ice_candidates',
        'started_at',
        'answered_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'offer_payload' => 'array',
            'answer_payload' => 'array',
            'ice_candidates' => 'array',
            'started_at' => 'datetime',
            'answered_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
