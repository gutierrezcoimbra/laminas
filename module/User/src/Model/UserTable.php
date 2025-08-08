<?php

declare(strict_types=1);

namespace User\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Where;

class UserTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getAdapter()
    {
        return $this->tableGateway->getAdapter();
    }

    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function getUser($id, $includeDeleted = false)
    {
        $id = (int) $id;
        $where = ['id' => $id];
        
        if (!$includeDeleted) {
            $where['deletedAt'] = 0;
        }
        
        $rowset = $this->tableGateway->select($where);
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("No se encontró el usuario con id $id");
        }
        
        return $row;
    }

    public function saveUser(User $user)
    {
        $data = $user->getArrayCopy();
        $id = (int) $user->getId();
        
        if ($id == 0) {
            unset($data['id']);
            $this->tableGateway->insert($data);
            $lastInsertId = $this->tableGateway->getLastInsertValue();
            $user->setId($lastInsertId);
        } else {
            if ($this->getUser($id, true)) {
                $this->tableGateway->update($data, ['id' => $id]);
            } else {
                throw new \Exception('Usuario con id ' . $id . ' no existe');
            }
        }
    }

    public function deleteUser($id)
    {
        $data = ['deletedAt' => 1];
        $adapter = $this->tableGateway->getAdapter();
        $statement = $adapter->createStatement('UPDATE users SET deletedAt = ? WHERE id = ?');
        $result = $statement->execute([$data['deletedAt'], $id]);
        
        if ($result->getAffectedRows() == 0) {
            throw new \Exception('No se pudo eliminar el usuario con id ' . $id);
        }
    }

    public function restoreUser($id)
    {
        $data = ['deletedAt' => 0];
        $this->tableGateway->update($data, ['id' => $id]);
    }

    public function getActiveUsers()
    {
        $where = new Where();
        $where->equalTo('deletedAt', 0);
        return $this->tableGateway->select($where);
    }

    public function getDeletedUsers()
    {
        $where = new Where();
        $where->equalTo('deletedAt', 1);
        return $this->tableGateway->select($where);
    }
    
    public function getUserByEmail($email, $includeDeleted = false)
    {
        $where = ['email' => $email];
        
        if (!$includeDeleted) {
            $where['deletedAt'] = 0;
        }
        
        $rowset = $this->tableGateway->select($where);
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("No se encontró el usuario con email $email");
        }
        
        return $row;
    }
} 
