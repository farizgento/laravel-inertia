<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratJalanPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'surat_jalan_id',
        'urutan',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'width',
        'height',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class);
    }
}
