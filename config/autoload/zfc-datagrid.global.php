<?php

return [
    'view_manager' => [
        'template_map' => [
            // Mapeo de plantillas si es necesario
        ],
        'template_path_stack' => [
            __DIR__ . '/../../vendor/zfc-datagrid/zfc-datagrid/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            // Helpers personalizados si es necesario
        ],
        'factories' => [
            'escapeHtml' => function($container) {
                $escaper = new \Laminas\View\Helper\EscapeHtml();
                // Configurar el escaper para manejar arrays
                return $escaper;
            },
        ],
    ],
    'zfcDatagrid' => [
        'cache' => [
            'adapter' => [
                'name' => 'Filesystem',
                'options' => [
                    'cache_dir' => './data/ZfcDatagrid',
                    'ttl' => 3600,
                ],
            ],
        ],
        'renderer' => [
            'http' => [
                'default' => 'bootstrapTable',
            ],
        ],
        'settings' => [
            'default' => [
                'pagination' => [
                    'all' => true,
                ],
                'locale' => 'es_ES',
            ],
        ],
    ],
];
