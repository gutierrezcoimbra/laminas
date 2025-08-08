CREATE DATABASE IF NOT EXISTS `laminastest` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE `laminastest`;

-- Crear la tabla de usuarios
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fechaNacimiento` date NOT NULL,
  `deletedAt` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Activo, 1=Borrado',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_deleted_at` (`deletedAt`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

