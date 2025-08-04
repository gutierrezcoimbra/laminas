<?php

declare(strict_types=1);

namespace User\Validator;

use Laminas\Validator\AbstractValidator;

class MinimumAge extends AbstractValidator
{
    const INVALID_AGE = 'invalidAge';
    const TOO_YOUNG = 'tooYoung';
    
    protected $messageTemplates = [
        self::INVALID_AGE => 'La fecha de nacimiento no es válida.',
        self::TOO_YOUNG => 'La edad mínima requerida es %minAge% años.',
    ];
    
    protected $messageVariables = [
        'minAge' => 'minAge',
    ];
    
    protected $minAge = 18;
    
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset($options['minAge'])) {
                $this->minAge = (int) $options['minAge'];
            }
        }
        
        parent::__construct($options);
    }
    
    public function isValid($value, $context = null)
    {
        $this->setValue($value);
        
        if (empty($value)) {
            return true; // Permitir valores vacíos si no son requeridos
        }
        
        try {
            $birthDate = new \DateTime($value);
            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;
            
            if ($age < $this->minAge) {
                $this->error(self::TOO_YOUNG);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error(self::INVALID_AGE);
            return false;
        }
    }
    
    public function setMinAge($minAge)
    {
        $this->minAge = (int) $minAge;
        return $this;
    }
    
    public function getMinAge()
    {
        return $this->minAge;
    }
} 