<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilter;
use User\Validator\UniqueSkillName;

class SkillForm extends Form
{
    public function __construct($skillTable = null, $excludeId = null)
    {
        parent::__construct('skill-form');
        
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        
        // ID (hidden field)
        $this->add([
            'name' => 'id',
            'type' => Element\Hidden::class,
        ]);
        
        // Nombre de la skill
        $this->add([
            'name' => 'nombre',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Nombre de la Skill',
                'label_attributes' => ['class' => 'control-label'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Ej: PHP, JavaScript, Laravel, React...',
                'required' => 'required',
                'maxlength' => 100,
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
        
        $this->addInputFilter($skillTable, $excludeId);
    }
    
    /**
     * Configura los filtros y validadores para cada campo del formulario
     * 
     * @param SkillTable|null $skillTable Tabla de skills para validación de nombre único
     * @param int|null $excludeId ID de la skill a excluir en validación de nombre único (para edición)
     */
    private function addInputFilter($skillTable = null, $excludeId = null): void
    {
        $inputFilter = new InputFilter();
        
        // ========================================
        // CAMPO: ID (Oculto)
        // ========================================
        $inputFilter->add([
            'name' => 'id',
            'required' => false,
            'filters' => [
                ['name' => \Laminas\Filter\ToInt::class],
            ],
        ]);
        
        // ========================================
        // CAMPO: NOMBRE
        // ========================================
        $inputFilter->add([
            'name' => 'nombre',
            'required' => true,
            'filters' => [
                // Elimina espacios en blanco al inicio y final
                ['name' => \Laminas\Filter\StringTrim::class],
                
                // Elimina todas las etiquetas HTML/XML (previene ataques XSS)
                ['name' => \Laminas\Filter\StripTags::class],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            \Laminas\Validator\NotEmpty::IS_EMPTY => 'El nombre de la skill es obligatorio',
                        ],
                    ],
                ],
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'min' => 2,
                        'max' => 100,
                        'messages' => [
                            \Laminas\Validator\StringLength::TOO_SHORT => 'El nombre de la skill debe tener al menos %min% caracteres',
                            \Laminas\Validator\StringLength::TOO_LONG => 'El nombre de la skill no puede tener más de %max% caracteres',
                        ],
                    ],
                ],
                [
                    'name' => UniqueSkillName::class,
                    'options' => [
                        'skillTable' => $skillTable,
                        'excludeId' => $excludeId,
                    ],
                ],
            ],
        ]);
        
        // Asigna el InputFilter configurado al formulario
        $this->setInputFilter($inputFilter);
    }
}
