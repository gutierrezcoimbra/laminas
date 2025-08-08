<?php

declare(strict_types=1);

namespace User\Model;

class Cv
{
    private ?int $id = null;
    private ?int $userId = null;
    private string $titulo = '';
    private ?string $resumen = null;
    private ?string $contenido = null;
    private ?float $pretensionSalarial = null;
    private array $skills = [];
    private int $deletedAt = 0;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    public function __construct(array $data = [])
    {
        $this->exchangeArray($data);
    }

    public function exchangeArray(array $data): void
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->userId = isset($data['userId']) ? (int) $data['userId'] : null;
        $this->titulo = $data['titulo'] ?? '';
        $this->resumen = $data['resumen'] ?? null;
        $this->contenido = $data['contenido'] ?? null;
        $this->pretensionSalarial = isset($data['pretension_salarial']) ? (float) $data['pretension_salarial'] : null;
        // Handle skills - can be array or JSON string
        if (isset($data['skills'])) {
            if (is_string($data['skills'])) {
                // If it's a JSON string, decode it
                $decodedSkills = json_decode($data['skills'], true);
                $this->skills = is_array($decodedSkills) ? $decodedSkills : [];
            } elseif (is_array($data['skills'])) {
                // If it's already an array, use it directly
                $this->skills = $data['skills'];
            } else {
                $this->skills = [];
            }
        } else {
            $this->skills = [];
        }
        $this->deletedAt = isset($data['deletedAt']) ? (int) $data['deletedAt'] : 0;
        
        if (isset($data['createdAt']) && !empty($data['createdAt'])) {
            try {
                $this->createdAt = new \DateTime($data['createdAt']);
            } catch (\Exception $e) {
                $this->createdAt = null;
            }
        }
        
        if (isset($data['updatedAt']) && !empty($data['updatedAt'])) {
            try {
                $this->updatedAt = new \DateTime($data['updatedAt']);
            } catch (\Exception $e) {
                $this->updatedAt = null;
            }
        }
    }

    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'titulo' => $this->titulo,
            'resumen' => $this->resumen,
            'contenido' => $this->contenido,
            'pretension_salarial' => $this->pretensionSalarial,
            'skills' => $this->skills,
            'deletedAt' => $this->deletedAt,
            'createdAt' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->userId; }
    public function getTitulo(): string { return $this->titulo; }
    public function getResumen(): ?string { return $this->resumen; }
    public function getContenido(): ?string { return $this->contenido; }
    public function getPretensionSalarial(): ?float { return $this->pretensionSalarial; }
    public function getSkills(): array { return $this->skills; }
    public function getDeletedAt(): int { return $this->deletedAt; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setUserId(?int $userId): void { $this->userId = $userId; }
    public function setTitulo(string $titulo): void { $this->titulo = $titulo; }
    public function setResumen(?string $resumen): void { $this->resumen = $resumen; }
    public function setContenido(?string $contenido): void { $this->contenido = $contenido; }
    public function setPretensionSalarial(?float $pretensionSalarial): void { $this->pretensionSalarial = $pretensionSalarial; }
    public function setSkills(array $skills): void { $this->skills = $skills; }
    public function setDeletedAt(int $deletedAt): void { $this->deletedAt = $deletedAt; }
}
