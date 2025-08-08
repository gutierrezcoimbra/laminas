<?php

declare(strict_types=1);

namespace User\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Escaper\Escaper;

class SafeEscapeHtml extends AbstractHelper
{
    protected $escaper;

    public function __construct()
    {
        $this->escaper = new Escaper('utf-8');
    }

    public function __invoke($value)
    {
        // Si es un array, no intentar escapar
        if (is_array($value)) {
            return $value;
        }
        
        // Si es null o vacío, retornar string vacío
        if ($value === null || $value === '') {
            return '';
        }
        
        // Si es un objeto, intentar convertir a string
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string) $value;
            } else {
                return '';
            }
        }
        
        // Escapar el valor
        return $this->escaper->escapeHtml((string) $value);
    }
}
