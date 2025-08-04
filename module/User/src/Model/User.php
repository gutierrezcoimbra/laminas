<?php

declare(strict_types=1);

namespace User\Model;

class User
{
    private ?int $id = null;
    private string $nombre = '';
    private string $apellidos = '';
    private string $email = '';
    private ?\DateTime $fechaNacimiento = null;
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
        $this->apellidos = $data['apellidos'] ?? '';
        $this->email = $data['email'] ?? '';
        
        // Simplificar manejo de fecha de nacimiento
        if (isset($data['fechaNacimiento']) && !empty($data['fechaNacimiento'])) {
            try {
                $this->fechaNacimiento = new \DateTime($data['fechaNacimiento']);
            } catch (\Exception $e) {
                $this->fechaNacimiento = null;
            }
        }
        
        $this->deletedAt = isset($data['deletedAt']) ? (int) $data['deletedAt'] : 0;
        
        // Simplificar manejo de timestamps
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
        $data = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'fechaNacimiento' => $this->fechaNacimiento ? $this->fechaNacimiento->format('Y-m-d') : null,
            'deletedAt' => $this->deletedAt,
        ];
        
        // Solo incluir createdAt y updatedAt si no es un nuevo registro
        if ($this->id !== null) {
            if ($this->createdAt) {
                $data['createdAt'] = $this->createdAt->format('Y-m-d H:i:s');
            }
            if ($this->updatedAt) {
                $data['updatedAt'] = $this->updatedAt->format('Y-m-d H:i:s');
            }
        }
        
        return $data;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellidos(): string
    {
        return $this->apellidos;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFechaNacimiento(): ?\DateTime
    {
        return $this->fechaNacimiento;
    }

    public function getDeletedAt(): int
    {
        return $this->deletedAt;
    }

    // Setters
    public function setId($id): void
    {
        $this->id = $id !== null ? (int) $id : null;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setApellidos(string $apellidos): void
    {
        $this->apellidos = $apellidos;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setFechaNacimiento($fechaNacimiento): void
    {
        if (empty($fechaNacimiento)) {
            $this->fechaNacimiento = null;
            return;
        }
        
        try {
            if (is_string($fechaNacimiento)) {
                $this->fechaNacimiento = new \DateTime($fechaNacimiento);
            } else {
                $this->fechaNacimiento = $fechaNacimiento;
            }
        } catch (\Exception $e) {
            $this->fechaNacimiento = null;
        }
    }

    public function setDeletedAt(int $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        if (is_string($createdAt)) {
            $this->createdAt = new \DateTime($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): void
    {
        if (is_string($updatedAt)) {
            $this->updatedAt = new \DateTime($updatedAt);
        } else {
            $this->updatedAt = $updatedAt;
        }
    }

    // Métodos de utilidad
    public function softDelete(): void
    {
        $this->deletedAt = 1;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt === 1;
    }

    public function restore(): void
    {
        $this->deletedAt = 0;
    }

    public function getNombreCompleto(): string
    {
        return trim($this->nombre . ' ' . $this->apellidos);
    }

    public function getEdad(): ?int
    {
        if (!$this->fechaNacimiento) {
            return null;
        }
        $hoy = new \DateTime();
        return $hoy->diff($this->fechaNacimiento)->y;
    }
} 
