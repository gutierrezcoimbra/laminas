<?php

return [
    // Configuración de localización
    'locale' => 'es_ES',
    
    // Configuración del traductor
    'translator' => [
        'locale' => 'es_ES',
        'translation_file_patterns' => [
            [
                'type'     => 'phparray',
                'base_dir' => getcwd() . '/data/language',
                'pattern'  => '%s.php',
            ],
        ],
    ],
    
    // Configuración de zona horaria y formato de fecha
    'php_settings' => [
        'date.timezone' => 'Europe/Madrid',
        'intl.default_locale' => 'es_ES',
    ],
    
    // Configuración de ZfcDatagrid en español
    'zfcDatagrid' => [
        'settings' => [
            'default' => [
                'locale' => 'es_ES',
                'dateformat' => [
                    'date' => 'd/m/Y',
                    'datetime' => 'd/m/Y H:i',
                    'time' => 'H:i',
                ],
            ],
        ],
    ],
];
