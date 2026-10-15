<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanAlat extends Model
{
    use HasFactory;

    public const CATEGORY_KEHILANGAN = 'kehilangan';
    public const CATEGORY_KERUSAKAN = 'kerusakan';

    public const STATUS_DILAPORKAN = 'Dilaporkan';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';

    /**
     * Status akhir laporan adalah Disetujui atau Ditolak. "Selesai" tidak lagi
     * dipakai untuk laporan baru dan hanya tersisa pada data lama, tetapi tetap
     * dihitung sebagai status final supaya ikut terlindungi aturan koreksi.
     */
    public const STATUS_SELESAI_LAMA = 'Selesai';

    /**
     * @var array<int, string>
     */
    public const STATUS_FINAL = [
        self::STATUS_DISETUJUI,
        self::STATUS_DITOLAK,
        self::STATUS_SELESAI_LAMA,
    ];

    protected $table = 'laporan_alats';

    protected $fillable = [
        'kategori',
        'deskripsi',
        'status',
        'tanggal_laporan',
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

    protected $casts = [
        'tanggal_laporan' => 'date',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(LaporanAlatPhoto::class, 'laporan_alat_id')->orderBy('urutan');
    }

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
