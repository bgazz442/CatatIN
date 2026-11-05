<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMaterialChunk extends Model
{
    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
        'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
