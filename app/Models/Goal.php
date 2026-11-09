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
        'goal_type',
        'description',
        'image_path',
        'target_value',
        'current_value',
        'progress_step',
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
            'progress_step' => 'decimal:2',
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

    public function formattedValue(float|string $value): string
    {
        $number = (float) $value;
        $formatted = number_format($number, 2, ',', '.');

        if ($this->goal_type === 'money') {
            $formatted = number_format($number, 0, ',', '.');

            return 'Rp '.$formatted;
        }

        if ($this->goal_type === 'percentage') {
            return rtrim(rtrim($formatted, '0'), ',').'%';
        }

        return $formatted.' '.$this->unit;
    }

    public function formattedProgressEntry(float|string $value): string
    {
        $amount = (float) $value;
        $sign = $amount < 0 ? '−' : '+';

        return $sign.$this->formattedValue(abs($amount));
    }

    public function remainingLabel(): string
    {
        $remaining = $this->formattedValue($this->remaining());

        if ($this->goal_type === 'money') {
            return 'Sisa '.$remaining.' ('.number_format(max(0, 100 - $this->percentage()), 1, ',', '.').'% lagi)';
        }

        if ($this->goal_type === 'percentage') {
            return 'Sisa '.$remaining.' lagi';
        }

        return number_format(max(0, 100 - $this->percentage()), 1, ',', '.').'% tersisa ('.$remaining.')';
    }
}
