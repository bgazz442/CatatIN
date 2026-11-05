<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyActivity extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'activity_date',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return ['activity_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function durationMinutes(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        return (int) Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time));
    }
}
