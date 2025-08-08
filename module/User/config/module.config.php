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
            // Rutas para CVs individuales (desde perfil de usuario)
            'cv' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/user/:userId/cv',
                    'defaults' => [
                        'controller' => Controller\CvController::class,
                        'action' => 'index',
                    ],
                    'constraints' => [
                        'userId' => '[0-9]+',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/view/:id',
                            'defaults' => [
                                'controller' => Controller\CvController::class,
                                'action' => 'view',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                ],
            ],
            // Nueva ruta para módulo CVs general
            'cvs' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/cvs',
                    'defaults' => [
                        'controller' => Controller\CvController::class,
                        'action' => 'all',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/view/:id',
                            'defaults' => [
                                'controller' => Controller\CvController::class,
                                'action' => 'viewGeneral',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/edit/:id',
                            'defaults' => [
                                'controller' => Controller\CvController::class,
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
                                'controller' => Controller\CvController::class,
                                'action' => 'delete',
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
                                'controller' => Controller\CvController::class,
                                'action' => 'add',
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
                $cvTable = $container->get(\User\Model\CvTable::class);
                return new \User\Controller\UserController($userTable, $cvTable);
            },
            Controller\CvController::class => function($container) {
                $cvTable = $container->get(\User\Model\CvTable::class);
                $userTable = $container->get(\User\Model\UserTable::class);
                $skillTable = $container->get(\User\Model\SkillTable::class);
                return new \User\Controller\CvController($cvTable, $userTable, $skillTable);
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
            // Nuevas factories para CVs
            \User\Model\CvTableGateway::class => function($container) {
                $dbAdapter = $container->get('Laminas\\Db\\Adapter\\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new \User\Model\Cv());
                return new \Laminas\Db\TableGateway\TableGateway('cvs', $dbAdapter, null, $resultSetPrototype);
            },
            \User\Model\CvTable::class => function($container) {
                $tableGateway = $container->get(\User\Model\CvTableGateway::class);
                return new \User\Model\CvTable($tableGateway);
            },
            // Factories para Skills
            \User\Model\SkillTableGateway::class => function($container) {
                $dbAdapter = $container->get('Laminas\\Db\\Adapter\\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new \User\Model\Skill());
                return new \Laminas\Db\TableGateway\TableGateway('skills', $dbAdapter, null, $resultSetPrototype);
            },
            \User\Model\SkillTable::class => function($container) {
                $tableGateway = $container->get(\User\Model\SkillTableGateway::class);
                return new \User\Model\SkillTable($tableGateway);
            },
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view',
        ],
        'template_map' => [
            'user/cv/index' => __DIR__ . '/../view/cv/index.phtml',
            'user/cv/view' => __DIR__ . '/../view/cv/view.phtml',
            'user/cv/all' => __DIR__ . '/../view/cv/all.phtml',
            'user/cv/view-general' => __DIR__ . '/../view/cv/view-general.phtml',
            'user/cv/delete' => __DIR__ . '/../view/cv/delete.phtml',
            'user/cv/add' => __DIR__ . '/../view/cv/add.phtml',
            'user/cv/edit' => __DIR__ . '/../view/cv/edit.phtml',
            'cv/partials/cv-form' => __DIR__ . '/../view/cv/partials/cv-form.phtml',
            'view-helpers/cv-display' => __DIR__ . '/../view/view-helpers/cv-display.phtml',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            View\Helper\FlashMessages::class => function($container) {
                return new View\Helper\FlashMessages();
            },
            View\Helper\ViewCVHelper::class => function($container) {
                $cvTable = $container->get(\User\Model\CvTable::class);
                return new View\Helper\ViewCVHelper($cvTable);
            },
            View\Helper\SafeEscapeHtml::class => function($container) {
                return new View\Helper\SafeEscapeHtml();
            },
        ],
        'aliases' => [
            'flashMessages' => View\Helper\FlashMessages::class,
            'viewCVHelper' => View\Helper\ViewCVHelper::class,
            'safeEscapeHtml' => View\Helper\SafeEscapeHtml::class,
        ],
    ],
];
