<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiWebsiteKnowledgeChunk extends Model
{
    protected $fillable = [
        'source_key',
        'feature',
        'route',
        'visibility',
        'version',
        'last_updated',
        'chunk_index',
        'content',
        'embedding',
        'content_hash',
        'source_hash',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata' => 'array',
            'last_updated' => 'date',
        ];
    }
}
