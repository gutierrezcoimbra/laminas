<?php

/**
 * Local Configuration Override for DEVELOPMENT MODE.
 *
 * This configuration override file is for providing configuration to use while
 * in development mode. Run:
 *
 * <code>
 * $ composer development-enable
 * </code>
 *
 * from the project root to copy this file to development.local.php and enable
 * the settings it contains.
 *
 * You may also create files matching the glob pattern `{,*.}{global,local}-development.php`.
 */

return [
    'view_manager' => [
        'display_exceptions' => true,
    ],
    'db' => [
        'driver' => 'Pdo',
        'dsn' => 'mysql:dbname=laminastest;host=localhost',
        'driver_options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ],
        'username' => 'root',
        'password' => '',
    ],
    'service_manager' => [
        'factories' => [
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\AdapterServiceFactory',
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'config:list' => \Laminas\Cli\Command\ConfigList::class,
            'route:list' => \Laminas\Cli\Command\RouteList::class,
            'service:list' => \Laminas\Cli\Command\ServiceList::class,
            'cache:clear' => \Laminas\Cli\Command\CacheClear::class,
            'module:list' => \Laminas\Cli\Command\ModuleList::class,
        ],
    ]
];
