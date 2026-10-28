<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidangPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'bidang_pekerjaans';

    protected $fillable = [
        'nama',
    ];

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}
