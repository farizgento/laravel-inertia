<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratJalan extends Model
{
    use HasFactory;

    public const TYPE_SHIPMENT = 'pengiriman';

    public const TYPE_RETURN = 'pengembalian';

    protected $table = 'surat_jalan';

    protected $fillable = [
        'peminjaman_id',
        'pengirim_nama',
        'jenis',
        'urutan',
        'nomor',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'generated_by',
        'generated_at',
        'template_version',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'size' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SuratJalanPhoto::class)->orderBy('urutan');
    }

    public function isShipment(): bool
    {
        return $this->jenis === self::TYPE_SHIPMENT;
    }

    public function isReturn(): bool
    {
        return $this->jenis === self::TYPE_RETURN;
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if ($this->isShipment()) {
            return route('pengiriman.surat-jalan-peminjaman.download', [
                'peminjaman' => $this->peminjaman_id,
            ], false);
        }

        if ($this->isReturn()) {
            return url('/storage/'.ltrim($this->path, '/'));
        }

        return null;
    }
}
