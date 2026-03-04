<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanItemPhoto extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_item_photos';

    protected $fillable = [
        'peminjaman_item_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PeminjamanItem::class, 'peminjaman_item_id');
    }
}
