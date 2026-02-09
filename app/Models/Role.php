<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const KEY_USER = 'user';
    public const KEY_SP_TOOL = 'sp_tool';
    public const KEY_PIC_TOOLS = 'pic_tools';
    public const KEY_MGR_TOOL = 'mgr_tool';
    public const KEY_ADMIN = 'admin';
    public const KEY_SUPER_ADMIN = 'super_admin';

    public const KEYS = [
        self::KEY_USER,
        self::KEY_SP_TOOL,
        self::KEY_PIC_TOOLS,
        self::KEY_MGR_TOOL,
        self::KEY_ADMIN,
        self::KEY_SUPER_ADMIN,
    ];

    protected $fillable = [
        'key',
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
