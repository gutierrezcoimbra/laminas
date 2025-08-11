<?php

declare(strict_types=1);

namespace User;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\EventManager\EventInterface;
use Laminas\Mvc\MvcEvent;

class Module implements ConfigProviderInterface, BootstrapListenerInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    /**
     * Registra listeners de eventos durante el bootstrap
     */
    public function onBootstrap(EventInterface $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();
        
        // Registrar el listener de cache de CV
        $cacheListener = $serviceManager->get(\User\Listener\CvCacheListener::class);
        $cacheListener->attach($eventManager);
    }
} 