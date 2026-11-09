<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            $removingActiveAdmin = $user->getOriginal('role') === 'admin'
                && ($user->isDirty('is_active') && ! $user->is_active
                    || $user->isDirty('role') && $user->role !== 'admin');

            if ($removingActiveAdmin && $user->getOriginal('is_active')
                && static::query()->where('role', 'admin')->where('is_active', true)->count() <= 1) {
                throw new LogicException('Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan rolenya.');
            }
        });

        static::deleting(function (User $user): void {
            if ($user->role === 'admin'
                && static::query()->where('role', 'admin')->count() <= 1) {
                throw new LogicException('Akun Admin terakhir tidak dapat dihapus.');
            }
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function catatan(): HasMany
    {
        return $this->hasMany(Catatan::class);
    }

    public function themePreference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }
}
