<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaAlatStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'alat_id',
        'source_peminjaman_id',
        'qty',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }

    public function sourcePeminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'source_peminjaman_id');
    }
}
