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
    
    private function addInputFilter($userTable = null, $excludeId = null): void
    {
        $inputFilter = new InputFilter();
        
        // ID
        $inputFilter->add([
            'name' => 'id',
            'required' => false,
            'filters' => [
                ['name' => \Laminas\Filter\ToInt::class],
            ],
        ]);
        
        // Nombre
        $inputFilter->add([
            'name' => 'nombre',
            'required' => true,
            'filters' => [
                ['name' => \Laminas\Filter\StringTrim::class],
                ['name' => \Laminas\Filter\StripTags::class],
                ['name' => NameFilter::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'min' => 2,
                        'max' => 50,
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
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'El nombre es obligatorio',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Apellidos
        $inputFilter->add([
            'name' => 'apellidos',
            'required' => true,
            'filters' => [
                ['name' => \Laminas\Filter\StringTrim::class],
                ['name' => \Laminas\Filter\StripTags::class],
                ['name' => NameFilter::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'min' => 2,
                        'max' => 100,
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
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'Los apellidos son obligatorios',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Email
        $emailValidators = [
            [
                'name' => \Laminas\Validator\EmailAddress::class,
                'options' => [
                    'messages' => [
                        \Laminas\Validator\EmailAddress::INVALID_FORMAT => 'El formato del email no es válido',
                    ],
                ],
            ],
            [
                'name' => \Laminas\Validator\NotEmpty::class,
                'options' => [
                    'messages' => [
                        \Laminas\Validator\NotEmpty::IS_EMPTY => 'El email es obligatorio',
                    ],
                ],
            ],
        ];
        
        // Agregar validador de email único si tenemos UserTable
        if ($userTable) {
            $emailValidators[] = [
                'name' => UniqueEmail::class,
                'options' => [
                    'userTable' => $userTable,
                    'excludeId' => $excludeId,
                ],
            ];
        }
        
        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'filters' => [
                ['name' => \Laminas\Filter\StringTrim::class],
                ['name' => \Laminas\Filter\StringToLower::class],
            ],
            'validators' => $emailValidators,
        ]);
        
        // Fecha de Nacimiento
        $inputFilter->add([
            'name' => 'fechaNacimiento',
            'required' => true,
            'filters' => [
                ['name' => \Laminas\Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'La fecha de nacimiento es obligatoria',
                        ],
                    ],
                ],
            ],
        ]);
        
        $this->setInputFilter($inputFilter);
    }
} 