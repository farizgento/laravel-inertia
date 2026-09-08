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
        'kode',
        'nama',
        'jenis_alat',
        'klasifikasi_alat',
        'lokasi',
        'total_aset',
        'area_id',
    ];

    protected static function booted(): void
    {
        // Kode hanya diisi otomatis bila pengguna tidak mengisinya sendiri.
        // Catatan: AlatImportService membuat alat lewat Alat::withoutEvents(),
        // jadi di sana kode dibuat secara eksplisit, bukan lewat hook ini.
        static::creating(function (self $alat): void {
            if (trim((string) $alat->kode) === '') {
                $alat->kode = self::generateKode($alat->area_id);
            }
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Awalan kode untuk sebuah area, mis. "TRLA". Dipakai bersama nomor urut.
     */
    public static function kodePrefix(?int $areaId): string
    {
        $areaKode = trim((string) (Area::query()->whereKey($areaId)->value('kode') ?? ''));

        return $areaKode !== '' ? $areaKode : 'AREA';
    }

    /**
     * Kode default untuk alat baru: nomor urut berikutnya di dalam area, dimulai
     * dari 1. Nomor diambil dari kode yang sudah ada dengan pola "<AWALAN>-<angka>",
     * sehingga kode yang sudah diubah pengguna ke format bebas tidak mengganggu
     * urutan. Bila ternyata masih bentrok, nomor dinaikkan sampai kosong.
     */
    public static function generateKode(?int $areaId): string
    {
        $prefix = self::kodePrefix($areaId);

        $tertinggi = 0;
        $kodeArea = self::query()
            ->where('area_id', $areaId)
            ->whereNotNull('kode')
            ->pluck('kode');

        foreach ($kodeArea as $kode) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $kode, $cocok)) {
                $tertinggi = max($tertinggi, (int) $cocok[1]);
            }
        }

        $terpakai = $kodeArea->map(fn ($kode) => (string) $kode)->all();

        do {
            $tertinggi++;
            $kandidat = $prefix . '-' . $tertinggi;
        } while (in_array($kandidat, $terpakai, true));

        return $kandidat;
    }

    public function areaStocks(): HasMany
    {
        return $this->hasMany(AreaAlatStock::class);
    }

    public function peminjamanTemplateItems(): HasMany
    {
        return $this->hasMany(PeminjamanTemplateItem::class);
    }
}
