<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [
    // Configuración de idioma español
    'translator' => [
        'locale' => 'es_ES',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => getcwd() . '/data/language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
    
    // Configuración de zona horaria
    'php_settings' => [
        'date.timezone' => 'Europe/Madrid',
    ],
];
