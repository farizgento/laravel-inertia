<?php

return [

    'default' => env('LDAP_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'hosts' => array_filter(array_map('trim', explode(',', env('LDAP_HOSTS', env('LDAP_HOST', '127.0.0.1'))))),
            'username' => env('LDAP_USERNAME'),
            'password' => env('LDAP_PASSWORD'),
            'port' => (int) env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN'),
            'timeout' => (int) env('LDAP_TIMEOUT', 5),
            'use_tls' => (bool) env('LDAP_TLS', false),
            'use_starttls' => (bool) env('LDAP_STARTTLS', false),
            'use_sasl' => (bool) env('LDAP_SASL', false),
            'sasl_options' => [
                // 'mech' => 'GSSAPI',
            ],
        ],
    ],

    'sync' => [
        'base_dn' => env('LDAP_SYNC_BASE_DN'),
        'unit_attribute' => env('LDAP_SYNC_UNIT_ATTRIBUTE', 'physicaldeliveryofficename'),
        'unit_values' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('LDAP_SYNC_UNIT_VALUES', 'Unit Bisnis Pemeliharaan,MAINTENANCE SERVICES UNIT'))
        ))),
    ],

    'logging' => [
        'enabled' => (bool) env('LDAP_LOGGING', true),
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'info'),
    ],

    'cache' => [
        'enabled' => (bool) env('LDAP_CACHE', false),
        'driver' => env('CACHE_DRIVER', 'file'),
    ],

];
