<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTranscription extends Model
{
    use HasFactory;

    protected $table = 'ai_transcriptions';

    protected $fillable = [
        'user_id',
        'title',
        'original_name',
        'source_path',
        'disk',
        'status',
        'mime_type',
        'size',
        'transcript',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
