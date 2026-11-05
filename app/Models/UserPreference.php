<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'accent_color',
        'background_color',
        'navigation_footer_color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
