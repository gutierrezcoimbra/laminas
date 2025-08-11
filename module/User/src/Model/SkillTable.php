<?php

declare(strict_types=1);

namespace User\Model;

use Laminas\Db\TableGateway\TableGateway;

class SkillTable
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

    public function getSkill($id)
    {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(['id' => $id, 'deletedAt' => 0]);
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("No se encontró la skill con id $id");
        }
        
        return $row;
    }

    public function saveSkillsForCv($cvId, $skillsData)
    {
        $cvId = (int) $cvId;
        $adapter = $this->tableGateway->getAdapter();
        
        // Primero eliminar todas las skills existentes del CV (soft delete)
        $sql = 'UPDATE skill_cv SET deletedAt = 1 WHERE cv_id = ?';
        $statement = $adapter->createStatement($sql);
        $statement->execute([$cvId]);
        
        // Ahora insertar/reactivar las nuevas skills
        if (!empty($skillsData)) {
            foreach ($skillsData as $skillData) {
                $skillId = (int) $skillData['skill_id'];
                $nivel = $skillData['nivel'];
                
                // Verificar si ya existe la relación (incluso si está eliminada)
                $checkSql = 'SELECT id, deletedAt FROM skill_cv WHERE skill_id = ? AND cv_id = ?';
                $checkStatement = $adapter->createStatement($checkSql);
                $checkResult = $checkStatement->execute([$skillId, $cvId]);
                $existingRow = $checkResult->current();
                
                if ($existingRow) {
                    // Actualizar la relación existente
                    $updateSql = 'UPDATE skill_cv SET nivel = ?, deletedAt = 0, updatedAt = CURRENT_TIMESTAMP WHERE id = ?';
                    $updateStatement = $adapter->createStatement($updateSql);
                    $updateStatement->execute([$nivel, $existingRow['id']]);
                } else {
                    // Crear nueva relación
                    $insertSql = 'INSERT INTO skill_cv (skill_id, cv_id, nivel, deletedAt) VALUES (?, ?, ?, 0)';
                    $insertStatement = $adapter->createStatement($insertSql);
                    $insertStatement->execute([$skillId, $cvId, $nivel]);
                }
            }
        }
        
        return true;
    }

    public function getCvSkills($cvId)
    {
        $adapter = $this->tableGateway->getAdapter();
        $sql = "SELECT s.id as skill_id, s.nombre, sc.nivel
                FROM skill_cv sc
                JOIN skills s ON sc.skill_id = s.id 
                WHERE sc.cv_id = ? 
                AND sc.deletedAt = 0 
                AND s.deletedAt = 0
                ORDER BY s.nombre";
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute([(int) $cvId]);
        
        $skills = [];
        foreach ($result as $row) {
            $skills[] = [
                'skill_id' => $row['skill_id'],
                'nombre' => $row['nombre'],
                'nivel' => $row['nivel']
            ];
        }
        
        return $skills;
    }

    /**
     * Búsqueda avanzada de skills usando FULLTEXT MATCH AGAINST
     * @param string $searchTerm Términos de búsqueda separados por espacios
     * @param int $limit Límite de resultados (default: 20)
     * @return array Array de skills con score de relevancia
     */
    public function searchSkillsFulltext($searchTerm, $limit = 20)
    {
        $adapter = $this->tableGateway->getAdapter();
        
        // Limpiar y preparar términos de búsqueda
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return [];
        }
        
        // Convertir términos a formato Boolean Mode (+término1 +término2)
        $terms = explode(' ', $searchTerm);
        $booleanTerms = [];
        foreach ($terms as $term) {
            $term = trim($term);
            if (!empty($term)) {
                $booleanTerms[] = '+' . $term . '*'; // Agregar wildcard para búsqueda parcial
            }
        }
        
        if (empty($booleanTerms)) {
            return [];
        }
        
        $booleanQuery = implode(' ', $booleanTerms);
        
        $sql = "SELECT 
                    id, 
                    nombre,
                    MATCH (nombre) AGAINST (? IN BOOLEAN MODE) AS score
                FROM skills 
                WHERE deletedAt = 0 
                AND MATCH (nombre) AGAINST (? IN BOOLEAN MODE) > 0
                ORDER BY score DESC, nombre ASC
                LIMIT ?";
        
        try {
            $statement = $adapter->createStatement($sql);
            $result = $statement->execute([$booleanQuery, $booleanQuery, (int) $limit]);
            
            $skills = [];
            foreach ($result as $row) {
                $skills[] = [
                    'id' => (int) $row['id'],
                    'nombre' => $row['nombre'],
                    'score' => (float) $row['score']
                ];
            }
            
            return $skills;
        } catch (\Exception $e) {
            // En caso de error con FULLTEXT, hacer búsqueda LIKE como fallback
            return $this->searchSkillsLike($searchTerm, $limit);
        }
    }

    /**
     * Búsqueda de skills usando LIKE como fallback
     * @param string $searchTerm Término de búsqueda
     * @param int $limit Límite de resultados
     * @return array Array de skills
     */
    private function searchSkillsLike($searchTerm, $limit = 20)
    {
        $adapter = $this->tableGateway->getAdapter();
        
        $sql = "SELECT 
                    id, 
                    nombre,
                    1.0 AS score
                FROM skills 
                WHERE deletedAt = 0 
                AND nombre LIKE ?
                ORDER BY nombre ASC
                LIMIT ?";
        
        $statement = $adapter->createStatement($sql);
        $result = $statement->execute(['%' . $searchTerm . '%', (int) $limit]);
        
        $skills = [];
        foreach ($result as $row) {
            $skills[] = [
                'id' => (int) $row['id'],
                'nombre' => $row['nombre'],
                'score' => 1.0
            ];
        }
        
        return $skills;
    }
}

