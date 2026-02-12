<?php

// config for Hwkdo/IntranetAppDokumente
return [
    /*
    |--------------------------------------------------------------------------
    | Matrix-Cache
    |--------------------------------------------------------------------------
    | TTL in Sekunden für die gecachte Zähler-Matrix (schnelleres Aufklappen).
    | 0 = Caching deaktivieren. Bei Änderung an Dokumenten wird der Cache automatisch geleert.
    */
    'matrix_cache_ttl' => (int) env('INTRANET_APP_DOKUMENTE_MATRIX_CACHE_TTL', 3600),

    'roles' => [
        'admin' => [
            'name' => 'App-Dokumente-Admin',
            'permissions' => [
                'see-app-dokumente',
                'manage-app-dokumente',
                'upload-app-dokumente',
            ]
        ],        
        'user' => [
            'name' => 'Benutzer',
            'permissions' => [
                'see-app-dokumente',                
            ],
            'add_to_existing' => true,
        ],
]
];
