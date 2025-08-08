<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilter;
use User\Validator\UniqueEmail;
use User\Filter\NameFilter;

class UserForm extends Form
{
    public function __construct($userTable = null, $excludeId = null)
    {
        parent::__construct('user-form');
        
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        
        // ID (hidden field)
        $this->add([
            'name' => 'id',
            'type' => Element\Hidden::class,
        ]);
        
        // Nombre
        $this->add([
            'name' => 'nombre',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Nombre',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Ingrese el nombre',
                'required' => 'required',
            ],
        ]);
        
        // Apellidos
        $this->add([
            'name' => 'apellidos',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Apellidos',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Ingrese los apellidos',
                'required' => 'required',
            ],
        ]);
        
        // Email
        $this->add([
            'name' => 'email',
            'type' => Element\Email::class,
            'options' => [
                'label' => 'Email',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'ejemplo@correo.com',
                'required' => 'required',
            ],
        ]);
        
        // Fecha de Nacimiento
        $this->add([
            'name' => 'fechaNacimiento',
            'type' => Element\Date::class,
            'options' => [
                'label' => 'Fecha de Nacimiento',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => 'required',
            ],
        ]);
        
        // Submit button
        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Guardar',
                'class' => 'btn btn-primary',
            ],
        ]);
        
        // Cancel button
        $this->add([
            'name' => 'cancel',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Cancelar',
                'class' => 'btn btn-secondary',
            ],
        ]);
        
        $this->addInputFilter($userTable, $excludeId);
    }
    
    /**
     * Configura los filtros y validadores para cada campo del formulario
     * 
     * Los filtros se aplican PRIMERO para limpiar/transformar los datos
     * Los validadores se ejecutan DESPUÉS para verificar los datos limpios
     * 
     * @param UserTable|null $userTable Tabla de usuarios para validación de email único
     * @param int|null $excludeId ID del usuario a excluir en validación de email único (para edición)
     */
    private function addInputFilter($userTable = null, $excludeId = null): void
    {
        $inputFilter = new InputFilter();
        
        // ========================================
        // CAMPO: ID (Oculto)
        // ========================================
        $inputFilter->add([
            'name' => 'id',
            'required' => false, // No es obligatorio para nuevos registros
            'filters' => [
                // Convierte el valor a entero (string -> int)
                // Ejemplo: "123" -> 123, "abc" -> 0
                ['name' => \Laminas\Filter\ToInt::class],
            ],
        ]);
        
        // ========================================
        // CAMPO: NOMBRE
        // ========================================
        $inputFilter->add([
            'name' => 'nombre',
            'required' => true, // Campo obligatorio
            'filters' => [
                // Elimina espacios en blanco al inicio y final
                // Ejemplo: "  Juan  " -> "Juan"
                ['name' => \Laminas\Filter\StringTrim::class],
                
                // Elimina todas las etiquetas HTML/XML (previene ataques XSS)
                // Ejemplo: "<b>Juan</b>" -> "Juan", "<script>alert('xss')</script>" -> "alert('xss')"
                ['name' => \Laminas\Filter\StripTags::class],
                
                // Filtro personalizado: Capitaliza palabras y limpia caracteres especiales
                // Ejemplo: "juan carlos" -> "Juan Carlos", "j0h@n" -> "Juan"
                ['name' => NameFilter::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'min' => 2,    // Mínimo 2 caracteres
                        'max' => 50,   // Máximo 50 caracteres
                        'messages' => [
                            \Laminas\Validator\StringLength::TOO_SHORT => 'El nombre debe tener al menos %min% caracteres',
                            \Laminas\Validator\StringLength::TOO_LONG => 'El nombre no puede tener más de %max% caracteres',
                        ],
                    ],
                ],
                [
                    'name' => \Laminas\Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            // Valida que el campo no esté vacío después de aplicar filtros
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'El nombre es obligatorio',
                        ],
                    ],
                ],
            ],
        ]);
        
        // ========================================
        // CAMPO: APELLIDOS
        // ========================================
        $inputFilter->add([
            'name' => 'apellidos',
            'required' => true, // Campo obligatorio
            'filters' => [
                // Elimina espacios en blanco al inicio y final
                // Ejemplo: "  Pérez López  " -> "Pérez López"
                ['name' => \Laminas\Filter\StringTrim::class],
                
                // Elimina todas las etiquetas HTML/XML (previene ataques XSS)
                // Ejemplo: "<i>Pérez</i> <b>López</b>" -> "Pérez López"
                ['name' => \Laminas\Filter\StripTags::class],
                
                // Filtro personalizado: Capitaliza palabras y limpia caracteres especiales
                // Ejemplo: "pérez lópez" -> "Pérez López", "p3r3z" -> "Perez"
                ['name' => NameFilter::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'min' => 2,     // Mínimo 2 caracteres
                        'max' => 100,   // Máximo 100 caracteres (más que nombre)
                        'messages' => [
                            \Laminas\Validator\StringLength::TOO_SHORT => 'Los apellidos deben tener al menos %min% caracteres',
                            \Laminas\Validator\StringLength::TOO_LONG => 'Los apellidos no pueden tener más de %max% caracteres',
                        ],
                    ],
                ],
                [
                    'name' => \Laminas\Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            // Valida que el campo no esté vacío después de aplicar filtros
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'Los apellidos son obligatorios',
                        ],
                    ],
                ],
            ],
        ]);
        
        // ========================================
        // CAMPO: EMAIL
        // ========================================
        $emailValidators = [
            [
                'name' => \Laminas\Validator\EmailAddress::class,
                'options' => [
                    'messages' => [
                        // Valida que sea una cadena de texto
                        \Laminas\Validator\EmailAddress::INVALID => 'Tipo de dato inválido. Se esperaba una cadena de texto',
                        
                        // Valida formato básico: usuario@dominio
                        \Laminas\Validator\EmailAddress::INVALID_FORMAT => 'El formato del email no es válido. Use el formato básico local-part@hostname',
                        
                        // Valida que el dominio sea un hostname válido
                        \Laminas\Validator\EmailAddress::INVALID_HOSTNAME => "'%hostname%' no es un hostname válido para la dirección de email",
                        
                        // Valida que el dominio tenga registros MX o A válidos
                        \Laminas\Validator\EmailAddress::INVALID_MX_RECORD => "'%hostname%' no parece tener registros MX o A válidos para la dirección de email",
                        
                        // Valida que no sea una dirección de red local
                        \Laminas\Validator\EmailAddress::INVALID_SEGMENT => "'%hostname%' no está en un segmento de red enrutable. La dirección de email no debe resolverse desde la red pública",
                        
                        // Valida formato dot-atom para la parte local
                        \Laminas\Validator\EmailAddress::DOT_ATOM => "'%localPart%' no puede coincidir con el formato dot-atom",
                        
                        // Valida formato quoted-string para la parte local
                        \Laminas\Validator\EmailAddress::QUOTED_STRING => "'%localPart%' no puede coincidir con el formato quoted-string",
                        
                        // Valida que la parte local sea válida
                        \Laminas\Validator\EmailAddress::INVALID_LOCAL_PART => "'%localPart%' no es una parte local válida para la dirección de email",
                        
                        // Valida que no exceda la longitud máxima
                        \Laminas\Validator\EmailAddress::LENGTH_EXCEEDED => 'La entrada excede la longitud permitida',
                    ],
                ],
            ],
            [
                'name' => \Laminas\Validator\NotEmpty::class,
                'options' => [
                    'messages' => [
                        // Valida que el campo no esté vacío después de aplicar filtros
                        \Laminas\Validator\NotEmpty::IS_EMPTY => 'El email es obligatorio',
                    ],
                ],
            ],
        ];
        
        // Agregar validador de email único si tenemos UserTable
        // Esto evita duplicados en la base de datos
        if ($userTable) {
            $emailValidators[] = [
                'name' => UniqueEmail::class,
                'options' => [
                    'userTable' => $userTable,  // Tabla para buscar emails existentes
                    'excludeId' => $excludeId,  // ID a excluir (para edición)
                ],
            ];
        }
        
        $inputFilter->add([
            'name' => 'email',
            'required' => true, // Campo obligatorio
            'filters' => [
                // Elimina espacios en blanco al inicio y final
                // Ejemplo: "  usuario@email.com  " -> "usuario@email.com"
                ['name' => \Laminas\Filter\StringTrim::class],
                
                // Convierte a minúsculas
                // Ejemplo: "Usuario@EMAIL.COM" -> "usuario@email.com"
                ['name' => \Laminas\Filter\StringToLower::class],
            ],
            'validators' => $emailValidators,
        ]);
        
        // ========================================
        // CAMPO: FECHA DE NACIMIENTO
        // ========================================
        $inputFilter->add([
            'name' => 'fechaNacimiento',
            'required' => true, // Campo obligatorio
            'filters' => [
                // Elimina espacios en blanco al inicio y final
                // Ejemplo: "  2023-01-15  " -> "2023-01-15"
                ['name' => \Laminas\Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            // Valida que el campo no esté vacío después de aplicar filtros
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'La fecha de nacimiento es obligatoria',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Asigna el InputFilter configurado al formulario
        $this->setInputFilter($inputFilter);
    }
}