<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public const ROLE_MOTHER = 'mother';
    public const ROLE_PROGRAM_STAFF = 'program_staff';
    public const ROLE_DSWD_STAFF = 'dswd_staff';

    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_FILE = 'file';
    public const TYPE_IEC_MATERIAL = 'iec_material';
    public const TYPE_MEDICAL_TEMPLATE = 'medical_template';
    public const TYPE_SYSTEM = 'system';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_role',
        'receiver_id',
        'receiver_role',
        'message_type',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'attachment_duration',
        'is_read',
        'read_at',
        'is_unsent',
        'unsent_at',
    ];

    protected function casts(): array
    {
        return [
            'attachment_size' => 'integer',
            'attachment_duration' => 'integer',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'is_unsent' => 'boolean',
            'unsent_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
