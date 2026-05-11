<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanAlat extends Model
{
    use HasFactory;

    public const CATEGORY_KEHILANGAN = 'kehilangan';
    public const CATEGORY_KERUSAKAN = 'kerusakan';

    protected $table = 'laporan_alats';

    protected $fillable = [
        'kategori',
        'deskripsi',
        'status',
        'jumlah',
        'alat_id',
        'user_id',
        'area_id',
        'source_peminjaman_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function sourcePeminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'source_peminjaman_id');
    }
}
