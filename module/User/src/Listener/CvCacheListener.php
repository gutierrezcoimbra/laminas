<?php

namespace User\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use User\Service\CvCacheService;

/**
 * Listener para manejar cache de vistas de CV
 */
class CvCacheListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;
    
    private CvCacheService $cacheService;
    
    public function __construct(CvCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    
    /**
     * Registra los listeners de eventos
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // Listener para verificar cache antes del dispatch
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'checkCache'],
            100 // Alta prioridad para ejecutar antes
        );
        
        // Listener para generar cache después del render
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_FINISH,
            [$this, 'generateCache'],
            -100 // Baja prioridad para ejecutar después
        );
    }
    
    /**
     * Verifica si existe cache y lo sirve si está disponible
     */
    public function checkCache(MvcEvent $event)
    {

        
        // Solo aplicar cache a la acción 'view' del CvController
        if (!$this->shouldCache($event)) {
            return;
        }
        
        $routeMatch = $event->getRouteMatch();
        $rawId = $routeMatch->getParam('id', 0);
        $cvId = (int) $rawId;
        
        // Validar que el ID sea positivo
        if ($cvId <= 0) {
            return;
        }
        
        try {
            // Clave de cache simple con hash determinístico - SIN consultas DB
            $deterministicHash = $this->generateCacheHash($cvId);
            $cacheKey = $this->cacheService->generateCacheKey($cvId, $deterministicHash);
            
            // Verificar si este CV está marcado para ignorar cache existente SOLO UNA VEZ
            $shouldIgnoreExistingCache = false;
            try {
                $session = new \Laminas\Session\Container('cv_cache_control');
                            if (isset($session->ignore_cache_once[$cvId]) && $session->ignore_cache_once[$cvId] === true) {
                $shouldIgnoreExistingCache = true;
                // ELIMINAR la marca inmediatamente - solo se usa una vez
                unset($session->ignore_cache_once[$cvId]);
            }
            } catch (\Exception $e) {
                // Continuar normalmente si hay error con sesión
            }
            
            // Verificar si existe cache (solo si no debemos ignorarlo)
            if (!$shouldIgnoreExistingCache && $this->cacheService->hasCache($cacheKey)) {
                $cachedHtml = $this->cacheService->getCachedHtml($cacheKey);
                
                if ($cachedHtml !== null) {
                    // Servir contenido desde cache
                    $this->serveCachedContent($event, $cachedHtml, $cacheKey);
                    return;
                }
            }
            
            // Guardar información para el listener de render (SIEMPRE generar nuevo cache)
            $event->setParam('cache_info', [
                'cv_id' => $cvId,
                'cache_key' => $cacheKey,
                'should_cache' => true
            ]);
            
        } catch (\Exception $e) {
            // En caso de error, continuar sin cache
            return;
        }
    }
    
    /**
     * Genera y guarda cache después del render
     */
    public function generateCache(MvcEvent $event)
    {

        
        $cacheInfo = $event->getParam('cache_info');
        
        // Solo generar cache si se marcó para hacerlo
        if (!$cacheInfo || !$cacheInfo['should_cache']) {
            return;
        }
        
        try {
            $response = $event->getResponse();
            
            // Solo cachear respuestas exitosas
            if (!$response instanceof Response || $response->getStatusCode() !== 200) {
                return;
            }
            
            $content = $response->getContent();
            
            // Verificar que el contenido no esté vacío
            if (empty($content)) {
                return;
            }
            
            // Guardar en cache
            $this->cacheService->setCachedHtml($cacheInfo['cache_key'], $content);
            
            // Añadir headers de cache también para contenido recién generado
            $headers = $this->cacheService->getCacheHeaders();
            foreach ($headers as $name => $value) {
                $response->getHeaders()->addHeaderLine($name, $value);
            }
            
            // Añadir header indicando que es contenido nuevo (MISS)
            $response->getHeaders()->addHeaderLine('X-Cache-Status', 'MISS');
            
        } catch (\Exception $e) {
            // En caso de error, continuar sin guardar cache
            return;
        }
    }
    
    /**
     * Sirve contenido desde cache
     */
    private function serveCachedContent(MvcEvent $event, string $cachedHtml, string $cacheKey)
    {
        $response = $event->getResponse();
        
        if ($response instanceof Response) {
            // Establecer contenido desde cache
            $response->setContent($cachedHtml);
            
            // Añadir headers de cache con TTL restante
            $headers = $this->cacheService->getCacheHitHeaders($cacheKey);
            foreach ($headers as $name => $value) {
                $response->getHeaders()->addHeaderLine($name, $value);
            }
            
            // Añadir header indicando que viene de cache
            $response->getHeaders()->addHeaderLine('X-Cache-Status', 'HIT');
            
            // Detener la ejecución normal del MVC
            $event->stopPropagation(true);
            $event->setResult($response);
            return $response;
        }
    }
    
    /**
     * Determina si se debe aplicar cache a la request actual
     */
    private function shouldCache(MvcEvent $event): bool
    {
        // Verificar si el cache está habilitado
        if (!$this->cacheService->isCacheEnabled()) {
            return false;
        }
        
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch) {
            return false;
        }
        
        // Aplicar cache a las acciones de vista de CV
        $controller = $routeMatch->getParam('controller');
        $action = $routeMatch->getParam('action');
        
        // Verificar que sea el CvController
        if ($controller !== \User\Controller\CvController::class) {
            return false;
        }
        
        // Solo aplicar cache a las acciones de vista: 'view' y 'viewGeneral'
        if (!in_array($action, ['view', 'viewGeneral'])) {
            return false;
        }
        
        // Verificar que tengamos un ID válido
        $rawId = $routeMatch->getParam('id', 0);
        $cvId = (int) $rawId;
        
        if ($cvId <= 0) {
            return false;
        }
        
        // La verificación de ignorar cache se hace en checkCache(), no aquí
        
        // Solo cachear requests GET
        $request = $event->getRequest();
        if ($request->getMethod() !== 'GET') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Genera un hash determinístico basado en el CV ID
     */
    private function generateCacheHash(int $cvId): string
    {
        return md5("cv_{$cvId}");
    }
}
