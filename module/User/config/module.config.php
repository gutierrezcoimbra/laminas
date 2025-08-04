<?php

declare(strict_types=1);

namespace User;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;


return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/view/:id',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action' => 'view',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'add' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/edit/:id',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action' => 'edit',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/delete/:id',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action' => 'delete',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\UserController::class => function($container) {
                $userTable = $container->get(\User\Model\UserTable::class);
                return new \User\Controller\UserController($userTable);
            },
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Laminas\Db\Adapter\Adapter' => function($container) {
                $config = $container->get('config');
                return new \Laminas\Db\Adapter\Adapter($config['db']);
            },
            \User\Model\UserTableGateway::class => function($container) {
                $dbAdapter = $container->get('Laminas\\Db\\Adapter\\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new \User\Model\User());
                return new \Laminas\Db\TableGateway\TableGateway('users', $dbAdapter, null, $resultSetPrototype);
            },
            \User\Model\UserTable::class => function($container) {
                $tableGateway = $container->get(\User\Model\UserTableGateway::class);
                return new \User\Model\UserTable($tableGateway);
            },
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            View\Helper\FlashMessages::class => function($container) {
                return new View\Helper\FlashMessages();
            },
        ],
        'aliases' => [
            'flashMessages' => View\Helper\FlashMessages::class,
        ],
    ],
];
