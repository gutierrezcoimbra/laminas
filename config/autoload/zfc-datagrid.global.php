<?php

return [
    'view_manager' => [
        'template_map' => [
            // Plantilla personalizada de paginación Bootstrap para ZfcDatagrid
            'zfc-datagrid/renderer/bootstrapTable/pagination' => __DIR__ . '/../../module/User/view/zfc-datagrid/renderer/bootstrapTable/pagination.phtml',
            'zfc-datagrid/renderer/bootstrapTable/paginator' => __DIR__ . '/../../module/User/view/zfc-datagrid/renderer/bootstrapTable/paginator.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../../vendor/zfc-datagrid/zfc-datagrid/view',
            __DIR__ . '/../../module/User/view', // Agregar el path de nuestras plantillas
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
            'bootstrapTable' => [
                'pagination' => [
                    'template' => 'zfc-datagrid/renderer/bootstrapTable/pagination',
                ],
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
