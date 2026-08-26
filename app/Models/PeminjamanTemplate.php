<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeminjamanTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'source_area_id',
        'created_by',
        'nama',
        'kategori',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function sourceArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'source_area_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PeminjamanTemplateItem::class);
    }
}
