<?php

declare(strict_types=1);

namespace User\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

class CvTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select(['deletedAt' => 0]);
    }

    public function getCvsByUserId($userId, $includeDeleted = false)
    {
        $userId = (int) $userId;
        $adapter = $this->tableGateway->getAdapter();
        
        $deletedCondition = $includeDeleted ? '' : 'AND cvs.deletedAt = 0';
        
        $sql = "SELECT cvs.*, 
                GROUP_CONCAT(
                    CONCAT(skills.id, ':', skills.nombre, ':', skill_cv.nivel) 
                    ORDER BY skills.nombre 
                    SEPARATOR '|'
                ) as skills_data
                FROM cvs 
                LEFT JOIN skill_cv ON cvs.id = skill_cv.cv_id AND skill_cv.deletedAt = 0
                LEFT JOIN skills ON skill_cv.skill_id = skills.id AND skills.deletedAt = 0
                WHERE cvs.userId = ? $deletedCondition
                GROUP BY cvs.id
                ORDER BY cvs.createdAt DESC";
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute([$userId]);
        
        $cvs = [];
        foreach ($result as $row) {
            $cvData = (array) $row;
            
            // Procesar las skills
            if (!empty($cvData['skills_data'])) {
                $skillsArray = [];
                $skillsData = explode('|', $cvData['skills_data']);
                foreach ($skillsData as $skillData) {
                    $parts = explode(':', $skillData);
                    if (count($parts) >= 3) {
                        // Formato: id:nombre:nivel
                        $skillsArray[] = [
                            'skill_id' => (int) $parts[0],
                            'nombre' => $parts[1], 
                            'nivel' => $parts[2]
                        ];
                    } elseif (count($parts) === 2) {
                        // Formato legacy: nombre:nivel (sin ID)
                        $skillsArray[] = [
                            'skill_id' => 0,
                            'nombre' => $parts[0], 
                            'nivel' => $parts[1]
                        ];
                    }
                }
                $cvData['skills'] = $skillsArray;
            } else {
                $cvData['skills'] = [];
            }
            
            unset($cvData['skills_data']);
            $cvs[] = new Cv($cvData);
        }
        
        return $cvs;
    }

    public function getCv($id, $includeDeleted = false)
    {
        $id = (int) $id;
        $where = ['id' => $id];
        
        if (!$includeDeleted) {
            $where['deletedAt'] = 0;
        }
        
        $rowset = $this->tableGateway->select($where);
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("No se encontró el CV con id $id");
        }
        
        return $row;
    }

    public function getCvWithSkills($id, $includeDeleted = false)
    {
        $id = (int) $id;
        $adapter = $this->tableGateway->getAdapter();
        
        $deletedCondition = $includeDeleted ? '' : 'AND cvs.deletedAt = 0';
        
        $sql = "SELECT cvs.*, 
                GROUP_CONCAT(
                    CONCAT(skills.id, ':', skills.nombre, ':', skill_cv.nivel) 
                    ORDER BY skills.nombre 
                    SEPARATOR '|'
                ) as skills_data
                FROM cvs 
                LEFT JOIN skill_cv ON cvs.id = skill_cv.cv_id AND skill_cv.deletedAt = 0
                LEFT JOIN skills ON skill_cv.skill_id = skills.id AND skills.deletedAt = 0
                WHERE cvs.id = ? $deletedCondition
                GROUP BY cvs.id";
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute([$id]);
        $cvData = $result->current();
        
        if (!$cvData) {
            throw new \Exception("No se encontró el CV con id $id");
        }
        
        // Procesar skills
        $skillsArray = [];
        if (!empty($cvData['skills_data'])) {
            $skillsData = explode('|', $cvData['skills_data']);
            foreach ($skillsData as $skillData) {
                $parts = explode(':', $skillData);
                if (count($parts) >= 3) {
                    // Formato: id:nombre:nivel
                    $skillsArray[] = [
                        'skill_id' => (int) $parts[0],
                        'nombre' => $parts[1], 
                        'nivel' => $parts[2]
                    ];
                } elseif (count($parts) === 2) {
                    // Formato legacy: nombre:nivel (sin ID)
                    $skillsArray[] = [
                        'skill_id' => 0,
                        'nombre' => $parts[0], 
                        'nivel' => $parts[1]
                    ];
                }
            }
        }
        $cvData['skills'] = $skillsArray;
        
        // Crear objeto CV
        $cv = new \User\Model\Cv();
        $cv->exchangeArray($cvData);
        
        return $cv;
    }

    public function getCvWithUser($cvId)
    {
        $adapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT cvs.*, 
                       users.nombre as user_nombre, 
                       users.apellidos as user_apellidos, 
                       users.email as user_email,
                       GROUP_CONCAT(
                           CONCAT(skills.nombre, ":", skill_cv.nivel) 
                           ORDER BY skills.nombre 
                           SEPARATOR "|"
                       ) as skills_data
                FROM cvs 
                JOIN users ON cvs.userId = users.id 
                LEFT JOIN skill_cv ON cvs.id = skill_cv.cv_id AND skill_cv.deletedAt = 0
                LEFT JOIN skills ON skill_cv.skill_id = skills.id AND skills.deletedAt = 0
                WHERE cvs.id = ? AND cvs.deletedAt = 0 AND users.deletedAt = 0
                GROUP BY cvs.id';
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute([(int) $cvId]);
        
        $row = $result->current();
        if (!$row) {
            throw new \Exception("No se encontró el CV con id $cvId");
        }
        
        $cvData = (object) $row;
        
        // Procesar las skills
        if (!empty($cvData->skills_data)) {
            $skillsArray = [];
            $skillsData = explode('|', $cvData->skills_data);
            foreach ($skillsData as $skillData) {
                if (strpos($skillData, ':') !== false) {
                    list($name, $level) = explode(':', $skillData, 2);
                    $skillsArray[] = ['nombre' => $name, 'nivel' => $level];
                }
            }
            $cvData->skills = $skillsArray;
        } else {
            $cvData->skills = [];
        }
        
        unset($cvData->skills_data);
        return $cvData;
    }

    public function getAllCvsWithUsers()
    {
        $adapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT cvs.*, 
                       users.nombre as user_nombre, 
                       users.apellidos as user_apellidos, 
                       users.email as user_email,
                       GROUP_CONCAT(
                           CONCAT(skills.nombre, ":", skill_cv.nivel) 
                           ORDER BY skills.nombre 
                           SEPARATOR "|"
                       ) as skills_data
                FROM cvs 
                JOIN users ON cvs.userId = users.id 
                LEFT JOIN skill_cv ON cvs.id = skill_cv.cv_id AND skill_cv.deletedAt = 0
                LEFT JOIN skills ON skill_cv.skill_id = skills.id AND skills.deletedAt = 0
                WHERE cvs.deletedAt = 0 AND users.deletedAt = 0 
                GROUP BY cvs.id
                ORDER BY cvs.createdAt DESC';
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute();
        
        $cvs = [];
        foreach ($result as $row) {
            $cvData = (object) $row;
            
            // Procesar las skills
            if (!empty($cvData->skills_data)) {
                $skillsArray = [];
                $skillsData = explode('|', $cvData->skills_data);
                foreach ($skillsData as $skillData) {
                    if (strpos($skillData, ':') !== false) {
                        list($name, $level) = explode(':', $skillData, 2);
                        $skillsArray[] = ['nombre' => $name, 'nivel' => $level];
                    }
                }
                $cvData->skills = $skillsArray;
            } else {
                $cvData->skills = [];
            }
            
            unset($cvData->skills_data);
            $cvs[] = $cvData;
        }
        
        return $cvs;
    }

    public function deleteCv($id)
    {
        $id = (int) $id;
        
        // Soft delete: marcar como eliminado
        $data = ['deletedAt' => 1];
        $this->tableGateway->update($data, ['id' => $id]);
        
        return true;
    }

    public function saveCv(Cv $cv)
    {
        $data = $cv->getArrayCopy();
        
        // Remover campos que no deben ser actualizados manualmente o no existen en la tabla
        unset($data['createdAt']);
        unset($data['updatedAt']);
        unset($data['skills']); // Skills se manejan en tabla separada
        
        $id = (int) $cv->getId();
        
        if ($id === 0) {
            // Nuevo CV
            unset($data['id']);
            $this->tableGateway->insert($data);
            return $this->tableGateway->getLastInsertValue();
        }
        
        // Actualizar CV existente
        if ($this->getCv($id)) {
            $this->tableGateway->update($data, ['id' => $id]);
            return $id;
        }
        
        throw new \Exception('No se pudo guardar el CV');
    }
}