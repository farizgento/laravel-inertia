<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanAlatPhoto extends Model
{
    use HasFactory;

    protected $table = 'laporan_alat_photos';

    protected $fillable = [
        'laporan_alat_id',
        'urutan',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'size' => 'integer',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanAlat::class, 'laporan_alat_id');
    }
}
