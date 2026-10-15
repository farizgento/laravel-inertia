<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PekerjaanOh extends Model
{
    use HasFactory;

    protected $table = 'pekerjaan_ohs';

    protected $fillable = [
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
