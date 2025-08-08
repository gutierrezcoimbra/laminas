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

    public function fetchAll()
    {
        return $this->tableGateway->select(['deletedAt' => 0]);
    }

    public function getCvsByUserId($userId, $includeDeleted = false)
    {
        $userId = (int) $userId;
        $where = ['userId' => $userId];
        
        if (!$includeDeleted) {
            $where['deletedAt'] = 0;
        }
        
        return $this->tableGateway->select($where);
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

    public function getCvWithUser($cvId)
    {
        $adapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT cvs.*, 
                       users.nombre as user_nombre, 
                       users.apellidos as user_apellidos, 
                       users.email as user_email
                FROM cvs 
                JOIN users ON cvs.userId = users.id 
                WHERE cvs.id = ? AND cvs.deletedAt = 0 AND users.deletedAt = 0';
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute([(int) $cvId]);
        
        $row = $result->current();
        if (!$row) {
            throw new \Exception("No se encontró el CV con id $cvId");
        }
        
        return (object) $row;
    }

    public function getAllCvsWithUsers()
    {
        $adapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT cvs.*, 
                       users.nombre as user_nombre, 
                       users.apellidos as user_apellidos, 
                       users.email as user_email
                FROM cvs 
                JOIN users ON cvs.userId = users.id 
                WHERE cvs.deletedAt = 0 AND users.deletedAt = 0 
                ORDER BY cvs.createdAt DESC';
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute();
        
        $cvs = [];
        foreach ($result as $row) {
            $cvs[] = (object) $row;
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
        
        // Remover campos que no deben ser actualizados manualmente
        unset($data['createdAt']);
        unset($data['updatedAt']);
        
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