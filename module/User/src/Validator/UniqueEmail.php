<?php

declare(strict_types=1);

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use User\Model\UserTable;

class UniqueEmail extends AbstractValidator
{
    const EMAIL_EXISTS = 'emailExists';
    
    protected $messageTemplates = [
        self::EMAIL_EXISTS => 'El email "%value%" ya está registrado en el sistema.',
    ];
    
    private $userTable;
    private $excludeId;
    
    public function __construct($options = null)
    {
        parent::__construct($options);
        
        if (is_array($options)) {
            if (isset($options['userTable'])) {
                $this->userTable = $options['userTable'];
            }
            if (isset($options['excludeId'])) {
                $this->excludeId = $options['excludeId'];
            }
        }
    }
    
    public function isValid($value, $context = null)
    {
        $this->setValue($value);
        
        // Si no tenemos UserTable, no podemos validar
        if (!$this->userTable) {
            return true;
        }
        
        try {
            // Buscar usuario por email
            $existingUser = $this->userTable->getUserByEmail($value);
            
            // Si encontramos un usuario y no es el que estamos editando
            if ($existingUser && $existingUser->getId() != $this->excludeId) {
                $this->error(self::EMAIL_EXISTS);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            // Si no se encuentra el usuario, el email es único
            return true;
        }
    }
    
    public function setUserTable(UserTable $userTable)
    {
        $this->userTable = $userTable;
        return $this;
    }
    
    public function setExcludeId($id)
    {
        $this->excludeId = $id;
        return $this;
    }
} 