<?php

use App\Models\User;

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

    'user_model' => env('INTRANET_APP_DOKUMENTE_USER_MODEL', User::class),

    'lightrag' => [
        'url' => env('LIGHTRAG_DOKUMENTE_URL', 'https://lightrag-dokumente.swarm.hwkdo.com'),
        'api_key' => env('LIGHTRAG_API_KEY'),
        'execute_in_tests' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | News-Rahmenbild (Dokument-Thumb wird in die Lücke gesetzt)
    |--------------------------------------------------------------------------
    | Maße beziehen sich auf die Default-Vorlage (news-frame-default.png)
    | im Format der News-Titelbilder (16:9, siehe config/news.php).
    | Bei abweichender Upload-Größe werden Slot-Koordinaten proportional skaliert.
    */
    'news_frame' => [
        'width' => 1920,
        'height' => 1080,
        'slot_x' => 641,
        'slot_y' => 269,
        'slot_width' => 675,
        'slot_height' => 750,
        // Nur Hinweis für die UI / Dokumentation (Medien-Conversion). Beim Compose füllt das Thumb die Slot-Größe.
        'thumb_max_width' => 320,
        'thumb_max_height' => 420,
        'default_filename' => 'news-frame-default.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Capability-Permissions (Defaults)
    |--------------------------------------------------------------------------
    | Können in den App-Einstellungen überschrieben werden. Rollenzuweisung
    | erfolgt über Spatie (z. B. nach intranet-app:sync-permissions).
    */
    'permissions' => [
        'upload' => 'upload-app-dokumente',
        'kenntnisnahme' => 'kenntnisnahme-app-dokumente',
        'choose_gvp' => 'choose-gvp-app-dokumente',
    ],

    'roles' => [
        'admin' => [
            'name' => 'App-Dokumente-Admin',
            'permissions' => [
                'see-app-dokumente',
                'manage-app-dokumente',
                'upload-app-dokumente',
                'kenntnisnahme-app-dokumente',
                'choose-gvp-app-dokumente',
            ],
        ],
        'uploader' => [
            'name' => 'App-Dokumente-Upload',
            'permissions' => [
                'see-app-dokumente',
                'upload-app-dokumente',
            ],
        ],
        'kenntnisnahme' => [
            'name' => 'App-Dokumente-Kenntnisnahme',
            'permissions' => [
                'see-app-dokumente',
                'upload-app-dokumente',
                'kenntnisnahme-app-dokumente',
            ],
        ],
        'gvp_chooser' => [
            'name' => 'App-Dokumente-GVP-Auswahl',
            'permissions' => [
                'see-app-dokumente',
                'upload-app-dokumente',
                'choose-gvp-app-dokumente',
            ],
        ],
        'user' => [
            'name' => 'Benutzer',
            'permissions' => [
                'see-app-dokumente',
            ],
            'add_to_existing' => true,
        ],
    ],
];
