<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Catatan extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'catatan';

    /**
     * Atribut yang dapat diisi secara massal (mass assignment).
     */
    protected $fillable = [
        'user_id',
        'judul',
        'kategori',
        'category_id',
        'isi',
        'favorit',
    ];

    /**
     * Konversi tipe data atribut (casting).
     */
    protected $casts = [
        'favorit' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Catatan $catatan) {
            if ($catatan->isDirty('kategori') || ! $catatan->category_id) {
                $category = Category::where('name', $catatan->kategori)->first();
                if ($category) {
                    $catatan->category_id = $category->id;
                }
            }

            if ($catatan->category_id && ($catatan->isDirty('category_id') || ! $catatan->kategori)) {
                $catatan->kategori = Category::whereKey($catatan->category_id)->value('name') ?? $catatan->kategori;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CatatanAttachment::class);
    }

    /**
     * Scope untuk pencarian judul dan isi catatan.
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('judul', 'like', '%' . $keyword . '%')
                  ->orWhere('isi', 'like', '%' . $keyword . '%');
            });
        }
        return $query;
    }

    /**
     * Scope untuk filter kategori.
     */
    public function scopeFilterKategori($query, ?string $kategori)
    {
        if (!empty($kategori)) {
            $query->where('kategori', $kategori);
        }
        return $query;
    }

    /**
     * Scope untuk filter favorit.
     */
    public function scopeFilterFavorit($query, $favorit = null)
    {
        if ($favorit !== null && $favorit !== '') {
            $query->where('favorit', (bool)$favorit);
        }
        return $query;
    }
}
