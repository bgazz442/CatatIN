<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanAttachment extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'extension',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function catatan(): BelongsTo
    {
        return $this->belongsTo(Catatan::class);
    }
}