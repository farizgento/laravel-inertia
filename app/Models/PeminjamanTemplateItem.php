<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'peminjaman_template_id',
        'alat_id',
        'qty',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PeminjamanTemplate::class, 'peminjaman_template_id');
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }
}
