<?php

/**
 * Configuración de Cache para la aplicación
 * 
 * Esta configuración define los adaptadores de cache y configuraciones específicas
 * para el cacheo de vistas de CV
 */

return [
    // Configuración general de cache
    'caches' => [
        // Cache por defecto del sistema
        'default' => [
            'adapter' => [
                'name' => 'Filesystem',
                'options' => [
                    'cache_dir' => './data/cache/system',
                    'ttl' => 3600, // 1 hora
                    'dir_level' => 2,
                    'dir_permission' => 0755,
                    'file_permission' => 0644,
                ],
            ],
            'plugins' => [
                'exception_handler' => [
                    'throw_exceptions' => false,
                ],
                'serializer' => [
                    'serializer' => 'Json',
                ],
            ],
        ],
        
        // Cache específico para vistas HTML de CVs
        'cv_html_cache' => [
            'adapter' => [
                'name' => 'Filesystem',
                'options' => [
                    'cache_dir' => './data/cache/cv_html',
                    'ttl' => 86400, // 24 horas - TTL configurable
                    'dir_level' => 0,
                    'dir_permission' => 0755,
                    'file_permission' => 0644,
                    'namespace' => 'cv_cache',
                    'namespace_separator' => '_', // Usar guión bajo en lugar de guión alto
                    'suffix' => 'html', // Extensión de archivo
                ],
            ],
            'plugins' => [
                'exception_handler' => [
                    'throw_exceptions' => false,
                ],
                // No usar serializer para HTML - guardar como texto plano
            ],
        ],
    ],
    
    // Configuración específica para cache de CVs
    'cv_cache' => [
        // Variable para activar/desactivar cache de CVs
        'enabled' => true,
        
        // TTL específico para cache de CVs (en segundos)
        'ttl' => 86400, // 24 horas - TTL configurable
        
        // Formato de clave de cache (el prefijo cv_cache_ se añade automáticamente)
        'key_format' => '%d_%s', // {cv_id}_{version_hash}
        
        
        // Headers HTTP para cache
        'http_headers' => [
            'Cache-Control' => 'public, max-age=86400',
            'Expires' => '+24 hours',
        ],
    ],
];
