<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peminjaman extends Model
{
    use HasFactory;

    public const STATUS_PERLU_DISETUJUI = 'Perlu Disetujui';

    public const STATUS_PERLU_DIREVIEW = 'Perlu Direview';

    public const STATUS_MENUNGGU_REVIEW = self::STATUS_PERLU_DISETUJUI;

    public const STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM = self::STATUS_PERLU_DIREVIEW;

    public const STATUS_DIPESAN = 'Dipesan';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_DIKIRIM = 'Dikirim';

    public const STATUS_DITERIMA = 'Diterima';

    public const STATUS_DIKEMBALIKAN_PARTIALS = 'Dikembalikan Partials';

    public const STATUS_DIKEMBALIKAN_SEMUANYA = 'Dikembalikan Semuanya';

    public const STATUS_SELESAI = 'Selesai';

    public const KATEGORI_INTRA_AREA = 'Intra Area';

    public const KATEGORI_ANTAR_AREA = 'Antar Area';

    protected $table = 'peminjamans';

    protected $fillable = [
        'user_id',
        'area_id',
        'requester_area_id',
        'is_inter_area',
        'pekerjaan',
        'catatan',
        'status',
        'kategori',
        'tanggal_pinjam',
        'tanggal_kembali',
        'review_note',
        'reviewed_at',
        'reviewed_by',
        'requester_review_note',
        'requester_reviewed_at',
        'requester_reviewed_by',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'date',
        'tanggal_kembali' => 'date',
        'reviewed_at' => 'datetime',
        'requester_reviewed_at' => 'datetime',
        'is_inter_area' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PeminjamanItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function requesterReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_reviewed_by');
    }

    public function suratJalan(): HasOne
    {
        return $this->hasOne(SuratJalan::class, 'peminjaman_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function requesterArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'requester_area_id');
    }

    public static function stockHoldingStatuses(): array
    {
        return [
            self::STATUS_MENUNGGU_REVIEW,
            self::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM,
            self::STATUS_DIPESAN,
            self::STATUS_DIKIRIM,
            self::STATUS_DITERIMA,
            self::STATUS_DIKEMBALIKAN_PARTIALS,
        ];
    }

    public static function shippingHistoryStatuses(): array
    {
        return [
            self::STATUS_DIKIRIM,
            self::STATUS_DITERIMA,
            self::STATUS_DIKEMBALIKAN_PARTIALS,
            self::STATUS_DIKEMBALIKAN_SEMUANYA,
            self::STATUS_SELESAI,
        ];
    }

    public static function returnableStatuses(): array
    {
        return [
            self::STATUS_DITERIMA,
            self::STATUS_DIKEMBALIKAN_PARTIALS,
        ];
    }

    public function determineReturnStatus(): string
    {
        $approvedTotal = (int) $this->items()->sum('approved_qty');
        $returnedTotal = (int) $this->items()->sum('returned_qty');

        if ($approvedTotal <= 0 || $returnedTotal <= 0) {
            return self::STATUS_DITERIMA;
        }

        if ($returnedTotal < $approvedTotal) {
            return self::STATUS_DIKEMBALIKAN_PARTIALS;
        }

        return self::STATUS_DIKEMBALIKAN_SEMUANYA;
    }
}
