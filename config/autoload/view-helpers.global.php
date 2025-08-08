<?php

return [
    'view_helpers' => [
        'factories' => [
            'escapeHtml' => function($container) {
                return new class {
                    private $escaper;
                    
                    public function __construct() {
                        $this->escaper = new \Laminas\Escaper\Escaper('utf-8');
                    }
                    
                    public function __invoke($value) {
                        // Si es un array, NO escapar - retornar tal como está
                        if (is_array($value)) {
                            return $value;
                        }
                        
                        // Si es null, retornar string vacío
                        if ($value === null) {
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
                        
                        // Escapar solo strings y números
                        return $this->escaper->escapeHtml((string) $value);
                    }
                };
            },
        ],
    ],
];
