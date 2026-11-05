<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'image_path',
        'target_value',
        'current_value',
        'unit',
        'start_date',
        'deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'start_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressEntries(): HasMany
    {
        return $this->hasMany(GoalProgressEntry::class);
    }

    public function percentage(): float
    {
        if ((float) $this->target_value <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->current_value / (float) $this->target_value) * 100, 1));
    }

    public function remaining(): float
    {
        return max(0, (float) $this->target_value - (float) $this->current_value);
    }
}
