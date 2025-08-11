<?php

namespace User\Service;

use Laminas\Cache\StorageFactory;
use Laminas\Cache\Storage\StorageInterface;

/**
 * Servicio para manejar el cache de vistas HTML de CVs
 */
class CvCacheService
{
    private StorageInterface $cache;
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->cache = StorageFactory::factory($config['caches']['cv_html_cache']);
    }
    
    /**
     * Verifica si el cache está habilitado
     */
    public function isCacheEnabled(): bool
    {
        return $this->config['cv_cache']['enabled'] ?? false;
    }
    
    /**
     * Genera la clave de cache para un CV
     */
    public function generateCacheKey(int $cvId, string $versionHash): string
    {
        // Con namespace 'cvcache', el archivo será: cvcache-{cvId}_{versionHash}.html
        return sprintf('%d_%s', $cvId, $versionHash);
    }
    
    /**
     * Verifica si existe cache para un CV
     */
    public function hasCache(string $cacheKey): bool
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }
        
        return $this->cache->hasItem($cacheKey);
    }
    
    /**
     * Obtiene el HTML cacheado de un CV
     */
    public function getCachedHtml(string $cacheKey): ?string
    {
        if (!$this->isCacheEnabled() || !$this->hasCache($cacheKey)) {
            return null;
        }
        
        return $this->cache->getItem($cacheKey);
    }
    
    /**
     * Obtiene headers específicos para contenido servido desde cache
     */
    public function getCacheHitHeaders(string $cacheKey): array
    {
        $headers = $this->getCacheHeaders();
        
        // Para cache HIT, intentar calcular TTL restante
        try {
            $metadata = $this->cache->getMetadata($cacheKey);
            if (isset($metadata['mtime'])) {
                $createdAt = $metadata['mtime'];
                $ttl = $this->config['cv_cache']['ttl'] ?? 3600;
                $expiresAt = $createdAt + $ttl;
                $remaining = max(0, $expiresAt - time());
                
                $headers['X-Cache-TTL-Remaining'] = $remaining;
                $headers['X-Cache-Created-At'] = gmdate('D, d M Y H:i:s', $createdAt) . ' GMT';
                $headers['X-Cache-Expires-At'] = gmdate('D, d M Y H:i:s', $expiresAt) . ' GMT';
            }
        } catch (\Exception $e) {
            // Si no podemos obtener metadata, usar headers por defecto
        }
        
        return $headers;
    }
    
    /**
     * Guarda el HTML de un CV en cache
     */
    public function setCachedHtml(string $cacheKey, string $html): bool
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }
        
        $ttl = $this->config['cv_cache']['ttl'];
        return $this->cache->setItem($cacheKey, $html, $ttl);
    }
    
    /**
     * Invalida el cache de un CV específico
     * Elimina todas las versiones cacheadas de un CV
     */
    public function invalidateCvCache(int $cvId): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }
        
        try {
            // Usar la misma lógica que CvCacheListener para generar la clave
            $deterministicHash = md5("cv_{$cvId}");
            $cacheKey = sprintf('%d_%s', $cvId, $deterministicHash);
            
            // Intentar eliminar usando la API de Laminas Cache
            $apiResult = false;
            if ($this->cache->hasItem($cacheKey)) {
                $apiResult = $this->cache->removeItem($cacheKey);
                // Verificar que realmente se eliminó
                if ($apiResult && !$this->cache->hasItem($cacheKey)) {
                    return true;
                }
            }
            
            // Si la API no funciona, eliminar directamente el archivo
            $cacheDir = './data/cache/cv_html';
            $pattern = $cacheDir . '/cv_cache_' . $cvId . '_*.html';
            $files = glob($pattern);
            
            $deleted = false;
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                    $deleted = true;
                }
            }
            
            return $deleted || true; // Éxito si eliminó archivos o no había nada que eliminar
        } catch (\Exception $e) {
            // En caso de error, intentar eliminación directa como fallback
            try {
                $cacheDir = './data/cache/cv_html';
                $pattern = $cacheDir . '/cv_cache_' . $cvId . '_*.html';
                $files = glob($pattern);
                
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                return true;
            } catch (\Exception $e2) {
                return false;
            }
        }
    }
    
    /**
     * Limpia todo el cache de CVs
     */
    public function clearAllCache(): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }
        
        try {
            return $this->cache->flush();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtiene los headers HTTP para cache
     */
    public function getCacheHeaders(): array
    {
        if (!$this->isCacheEnabled()) {
            return [];
        }
        
        $headers = $this->config['cv_cache']['http_headers'] ?? [];
        $ttl = $this->config['cv_cache']['ttl'] ?? 3600;
        
        // Reemplazar placeholders dinámicos
        foreach ($headers as $key => $value) {
            if ($value === '+1 hour') {
                $headers[$key] = gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT';
            }
        }
        
        // Añadir headers informativos de TTL
        $headers['X-Cache-TTL'] = $ttl; // TTL total en segundos
        $headers['X-Cache-TTL-Remaining'] = $ttl; // TTL restante (para nuevos será igual al total)
        $headers['X-Cache-Expires-At'] = gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT';
        $headers['X-Cache-Created-At'] = gmdate('D, d M Y H:i:s') . ' GMT';
        
        return $headers;
    }
}
