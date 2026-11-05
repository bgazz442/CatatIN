<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function catatan(): HasMany
    {
        return $this->hasMany(Catatan::class, 'category_id');
    }

    public function mediaFavorites(): HasMany
    {
        return $this->hasMany(MediaFavorite::class, 'category_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'category_id');
    }

    public function dailyActivities(): HasMany
    {
        return $this->hasMany(DailyActivity::class, 'category_id');
    }
}