<?php

declare(strict_types=1);

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use User\Model\SkillTable;

class UniqueSkillName extends AbstractValidator
{
    const SKILL_EXISTS = 'skillExists';
    
    protected $messageTemplates = [
        self::SKILL_EXISTS => 'Ya existe una skill con el nombre "%value%". Por favor, elija un nombre diferente.',
    ];
    
    private $skillTable;
    private $excludeId;
    
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset($options['skillTable'])) {
                $this->skillTable = $options['skillTable'];
            }
            if (isset($options['excludeId'])) {
                $this->excludeId = (int) $options['excludeId'];
            }
        }
        
        parent::__construct($options);
    }
    
    public function isValid($value)
    {
        $this->setValue($value);
        
        if (!$this->skillTable) {
            // Si no hay tabla, no podemos validar, así que asumimos que es válido
            return true;
        }
        
        // Limpiar el valor (trim y convertir a string)
        $cleanValue = trim((string) $value);
        
        if (empty($cleanValue)) {
            // Si está vacío, no es nuestro trabajo validarlo (eso lo hace NotEmpty)
            return true;
        }
        
        try {
            // Buscar skills con el mismo nombre (case-insensitive)
            $adapter = $this->skillTable->getTableGateway()->getAdapter();
            $sql = new \Laminas\Db\Sql\Sql($adapter);
            $select = $sql->select();
            $select->from('skills')
                   ->where(['deletedAt' => 0])
                   ->where->like('nombre', $cleanValue);
            
            // Si estamos editando, excluir el ID actual
            if ($this->excludeId) {
                $select->where->notEqualTo('id', $this->excludeId);
            }
            
            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();
            
            // Si encontramos algún resultado, significa que ya existe
            if ($result->count() > 0) {
                $this->error(self::SKILL_EXISTS);
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            // En caso de error de base de datos, loguear y permitir continuar
            error_log('Error en validación de skill única: ' . $e->getMessage());
            return true;
        }
    }
}
