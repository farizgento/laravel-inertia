<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'jenis_alat',
        'klasifikasi_alat',
        'total_aset',
        'area_id',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function getKodeAttribute(): string
    {
        $areaKode = trim((string) ($this->area?->kode ?? ''));

        return ($areaKode !== '' ? $areaKode : 'AREA') . '-' . $this->id;
    }

    public function areaStocks(): HasMany
    {
        return $this->hasMany(AreaAlatStock::class);
    }
}
