<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'url',
        'platform',
        'video_id',
        'category_id',
        'description',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return ['is_favorite' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}