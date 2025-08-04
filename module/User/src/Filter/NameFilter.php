<?php

declare(strict_types=1);

namespace User\Filter;

use Laminas\Filter\AbstractFilter;

class NameFilter extends AbstractFilter
{
    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Eliminar espacios extra y caracteres no deseados
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value); // Reemplazar múltiples espacios con uno solo
        
        // Capitalizar primera letra de cada palabra
        $value = ucwords(strtolower($value));
        
        // Eliminar caracteres especiales excepto espacios, guiones y apóstrofes
        $value = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\'-]/', '', $value);
        
        return $value;
    }
} 