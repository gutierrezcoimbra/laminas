<?php

declare(strict_types=1);

namespace User\Model;

class Skill
{
    private ?int $id = null;
    private string $nombre = '';
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
        $this->nombre = $data['nombre'] ?? '';
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
            'nombre' => $this->nombre,
            'deletedAt' => $this->deletedAt,
            'createdAt' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getDeletedAt(): int { return $this->deletedAt; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setDeletedAt(int $deletedAt): void { $this->deletedAt = $deletedAt; }
}
