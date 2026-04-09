<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'area_id',
        'subject_id',
        'actor_name',
        'actor_role_key',
        'actor_area_id',
        'action',
        'subject_type',
        'subject_label',
        'description',
        'method',
        'route',
        'url',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'properties',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
