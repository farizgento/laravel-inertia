<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PekerjaanOh extends Model
{
    use HasFactory;

    protected $table = 'pekerjaan_ohs';

    protected $fillable = [
        'area_id',
        'jenis',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'siteco',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** Satu pekerjaan overhaul bisa memiliki banyak peminjaman. */
    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'pekerjaan_oh_id');
    }

    /**
     * Lama OH dihitung inklusif: 25 Des 2026 - 4 Feb 2027 berarti 42 hari.
     */
    public function lamaOh(): ?int
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return null;
        }

        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }
}
