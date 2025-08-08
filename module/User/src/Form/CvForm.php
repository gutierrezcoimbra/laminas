<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilter;

class CvForm extends Form
{
    private $userTable;
    private $skillsTable;
    
    public function __construct($userTable = null, $skillsTable = null)
    {
        parent::__construct('cv-form');
        
        $this->userTable = $userTable;
        $this->skillsTable = $skillsTable;
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        
        // ID (hidden field)
        $this->add([
            'name' => 'id',
            'type' => Element\Hidden::class,
        ]);
        
        // Usuario Select
        $this->add([
            'name' => 'userId',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Usuario',
                'label_attributes' => ['class' => 'control-label'],
                'value_options' => $this->getUserOptions(),
                'empty_option' => 'Seleccione un usuario...',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => 'required',
            ],
        ]);
        
        // Título
        $this->add([
            'name' => 'titulo',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Título del CV',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Ej: Desarrollador Full Stack, Ingeniero de Software, etc.',
                'required' => 'required',
                'maxlength' => 150,
            ],
        ]);
        
        // Resumen
        $this->add([
            'name' => 'resumen',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Resumen Profesional',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Breve descripción del perfil profesional, experiencia y competencias principales...',
                'rows' => 4,
                'maxlength' => 500,
            ],
        ]);
        
        // Contenido
        $this->add([
            'name' => 'contenido',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Contenido Detallado',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Experiencia laboral detallada, educación, habilidades técnicas, proyectos, logros, etc...',
                'rows' => 10,
            ],
        ]);
        
        // Skills - Campo dinámico para múltiples skills
        $this->add([
            'name' => 'skills',
            'type' => Element\Hidden::class,
            'attributes' => [
                'id' => 'skills-data',
                'class' => 'skills-hidden-field',
                'value' => '', // Inicializar como string vacío
            ],
        ]);
        
        // Submit button
        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Guardar CV',
                'class' => 'btn btn-success',
            ],
        ]);
        
        // Cancel button
        $this->add([
            'name' => 'cancel',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Cancelar',
                'class' => 'btn btn-secondary',
                'formnovalidate' => 'formnovalidate',
            ],
        ]);
        
        // Set up input filter
        $this->setInputFilter($this->createInputFilter());
    }
    
    private function getUserOptions(): array
    {
        $options = [];
        
        if ($this->userTable) {
            try {
                $users = $this->userTable->getActiveUsers();
                foreach ($users as $user) {
                    $options[$user->getId()] = $user->getNombreCompleto() . ' (' . $user->getEmail() . ')';
                }
            } catch (\Exception $e) {
                // En caso de error, devolver array vacío
            }
        }
        
        return $options;
    }
    
    public function getAvailableSkills(): array
    {
        $skills = [];
        
        if ($this->skillsTable) {
            try {
                $skillsData = $this->skillsTable->fetchAll();
                foreach ($skillsData as $skill) {
                    $skills[] = [
                        'id' => $skill->getId(),
                        'nombre' => $skill->getNombre()
                    ];
                }
            } catch (\Exception $e) {
                // En caso de error, devolver array vacío
            }
        }
        
        return $skills;
    }
    
    private function createInputFilter(): InputFilter
    {
        $inputFilter = new InputFilter();
        
        // Usuario ID
        $inputFilter->add([
            'name' => 'userId',
            'required' => true,
            'filters' => [
                ['name' => 'ToInt'],
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Debe seleccionar un usuario',
                        ],
                    ],
                ],
                [
                    'name' => 'GreaterThan',
                    'options' => [
                        'min' => 0,
                        'messages' => [
                            'notGreaterThan' => 'Debe seleccionar un usuario válido',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Título
        $inputFilter->add([
            'name' => 'titulo',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'El título del CV es obligatorio',
                        ],
                    ],
                ],
                [
                    'name' => 'StringLength',
                    'options' => [
                        'min' => 3,
                        'max' => 150,
                        'messages' => [
                            'stringLengthTooShort' => 'El título debe tener al menos 3 caracteres',
                            'stringLengthTooLong' => 'El título no puede exceder 150 caracteres',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Resumen
        $inputFilter->add([
            'name' => 'resumen',
            'required' => false,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => 500,
                        'messages' => [
                            'stringLengthTooLong' => 'El resumen no puede exceder 500 caracteres',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Contenido
        $inputFilter->add([
            'name' => 'contenido',
            'required' => false,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => 10000,
                        'messages' => [
                            'stringLengthTooLong' => 'El contenido no puede exceder 10,000 caracteres',
                        ],
                    ],
                ],
            ],
        ]);
        
        // Skills
        $inputFilter->add([
            'name' => 'skills',
            'required' => false,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
        ]);
        
        return $inputFilter;
    }
}
