<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LdapUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'ldap_dn',
        'name',
        'username',
        'email',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];
}
