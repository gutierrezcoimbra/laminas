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

-- Insertar algunos datos de ejemplo
INSERT INTO `users` (`id`,`nombre`, `apellidos`, `email`, `fechaNacimiento`) VALUES
(1,'Juan', 'Pérez García', 'juan.perez@email.com', '1990-05-15'),
(2,'María', 'González López', 'maria.gonzalez@email.com', '1985-08-22'),
(3,'Carlos', 'Rodríguez Martínez', 'carlos.rodriguez@email.com', '1992-03-10'),
(4,'Ana', 'Fernández Ruiz', 'ana.fernandez@email.com', '1988-12-05'),
(5,'Luis', 'Sánchez Moreno', 'luis.sanchez@email.com', '1995-07-18');

-- Crear índices adicionales para mejorar el rendimiento
CREATE INDEX `idx_nombre_apellidos` ON `users` (`nombre`, `apellidos`);
CREATE INDEX `idx_fecha_nacimiento` ON `users` (`fechaNacimiento`);


CREATE TABLE `cvs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `resumen` text NULL,
  `contenido` mediumtext NULL,
  `pretension_salarial` DECIMAL(19,2) NULL DEFAULT NULL ,
  `deletedAt` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Activo, 1=Borrado',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_is_deleted` (`userId`, `deletedAt`),
  CONSTRAINT `fk_cvs_user`
    FOREIGN KEY (`userId`) REFERENCES `users`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cvs` (`userId`, `titulo`, `resumen`, `contenido`) VALUES
(1, 'CV Profesional - Juan Pérez García', 'Resumen profesional de Juan Pérez', 'Experiencia en desarrollo y gestión de proyectos'),
(1, 'CV Académico - Juan Pérez García', 'Resumen académico de Juan Pérez', 'Formación universitaria y certificaciones técnicas'),
(2, 'CV Ejecutivo - María González López', 'Resumen ejecutivo de María González', 'Liderazgo en equipos multidisciplinarios'),
(3, 'CV Técnico - Carlos Rodríguez Martínez', 'Resumen técnico de Carlos Rodríguez', 'Especialista en tecnologías web y bases de datos'),
(3, 'CV Gerencial - Carlos Rodríguez Martínez', 'Resumen gerencial de Carlos Rodríguez', 'Gestión de proyectos y coordinación de equipos'),
(4, 'CV Profesional - Ana Fernández Ruiz', 'Resumen profesional de Ana Fernández', 'Experiencia en análisis y consultoría empresarial'),
(5, 'CV Especialista - Luis Sánchez Moreno', 'Resumen especializado de Luis Sánchez', 'Expertise en desarrollo de software y arquitectura'),
(5, 'CV Consultor - Luis Sánchez Moreno', 'Resumen como consultor de Luis Sánchez', 'Consultoría técnica y mentoring en tecnología');


CREATE TABLE `skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `deletedAt` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Activo, 1=Borrado',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_skill_nombre` (`nombre`),
  KEY `idx_deleted_at` (`deletedAt`),
  FULLTEXT KEY `ft_skill_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `skill_cv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_id` int(11) NOT NULL,
  `cv_id` int(11) NOT NULL,
  `nivel` ENUM('basico', 'intermedio', 'avanzado', 'experto') NOT NULL DEFAULT 'basico',
  `deletedAt` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Activo, 1=Borrado',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_skill_cv` (`skill_id`, `cv_id`),
  KEY `idx_skill` (`skill_id`),
  KEY `idx_cv` (`cv_id`),
  KEY `idx_nivel` (`nivel`),
  KEY `idx_deleted_at` (`deletedAt`),
  CONSTRAINT `fk_skill_cv_skill`
    FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_skill_cv_cv`
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `skills` (`nombre`) VALUES
-- Lenguajes de Programación
('PHP'),
('JavaScript'),
('TypeScript'),
('Python'),
('Java'),
('C#'),
('C++'),
('Ruby'),
('Go'),
('Rust'),
('Swift'),
('Kotlin'),
('Scala'),
('R'),
('MATLAB'),

-- Frontend y UI/UX
('HTML5'),
('CSS3'),
('SASS'),
('LESS'),
('React.js'),
('Vue.js'),
('Angular'),
('Svelte'),
('jQuery'),
('Bootstrap'),
('Tailwind CSS'),
('Material Design'),
('Figma'),
('Adobe Photoshop'),
('Adobe Illustrator'),

-- Backend y Frameworks
('Node.js'),
('Express.js'),
('Laravel'),
('Symfony'),
('Django'),
('Flask'),
('Spring Boot'),
('ASP.NET Core'),
('Ruby on Rails'),
('FastAPI'),

-- Bases de Datos
('MySQL'),
('PostgreSQL'),
('MongoDB'),
('Redis'),
('SQLite'),
('Oracle Database'),
('Microsoft SQL Server'),
('Elasticsearch'),
('Apache Cassandra'),
('Neo4j'),

-- DevOps y Cloud
('Docker'),
('Kubernetes'),
('Jenkins'),
('GitLab CI'),
('GitHub Actions'),
('AWS'),
('Microsoft Azure'),
('Google Cloud Platform'),
('Terraform'),
('Ansible');


INSERT INTO `skill_cv` (`skill_id`, `cv_id`, `nivel`) VALUES
-- CV 1 (Desarrollador Full Stack)
(1, 1, 'avanzado'),    -- PHP
(2, 1, 'avanzado'),    -- JavaScript
(16, 1, 'intermedio'), -- HTML5
(17, 1, 'intermedio'), -- CSS3
(28, 1, 'avanzado'),   -- Laravel
(32, 1, 'intermedio'), -- MySQL

-- CV 2 (Desarrollador Frontend)
(2, 2, 'experto'),     -- JavaScript
(3, 2, 'avanzado'),    -- TypeScript
(20, 2, 'experto'),    -- React.js
(21, 2, 'intermedio'), -- Vue.js
(25, 2, 'avanzado'),   -- Bootstrap

-- CV 3 (Data Scientist)
(4, 3, 'experto'),     -- Python
(14, 3, 'avanzado'),   -- R
(15, 3, 'intermedio'), -- MATLAB
(34, 3, 'intermedio'), -- MongoDB
(39, 3, 'basico');     -- Elasticsearch


DROP VIEW IF EXISTS cv_datagrid_view;

CREATE VIEW cv_datagrid_view AS
SELECT 
    c.id as cv_id,
    c.titulo,
    -- Truncar resumen a 150 caracteres con '...' si es más largo
    CASE 
        WHEN LENGTH(c.resumen) > 100 
        THEN CONCAT(SUBSTRING(c.resumen, 1, 100), '...') 
        ELSE c.resumen 
    END as resumen,
    c.pretension_salarial,
    
    -- Usuario: Nombre completo y email combinados en una sola columna
    u.id as user_id,
    CONCAT(u.nombre, ' ', u.apellidos) as user_nombre_completo,
    u.email as user_email,
    CONCAT(u.nombre, ' ', u.apellidos, '\n', u.email) as user_info_combined,
    
    -- Skills concatenadas con badges
    GROUP_CONCAT(
        CONCAT(s.nombre, ':', sc.nivel) 
        ORDER BY sc.nivel DESC, s.nombre ASC 
        SEPARATOR '|'
    ) as skills_with_levels
    
FROM cvs c
INNER JOIN users u ON c.userId = u.id
LEFT JOIN skill_cv sc ON c.id = sc.cv_id AND sc.deletedAt = 0
LEFT JOIN skills s ON sc.skill_id = s.id AND s.deletedAt = 0
WHERE c.deletedAt = 0 AND u.deletedAt = 0
GROUP BY c.id, u.id;


    
    
-- Crear vista para usuarios con edad calculada y conteo de CVs


DROP VIEW IF EXISTS users_datagrid_view;

CREATE VIEW users_datagrid_view AS
SELECT 
    u.id as user_id,
    u.nombre,
    u.apellidos,
    CONCAT(u.nombre, ' ', u.apellidos) as nombre_completo,
    u.email,
    u.fechaNacimiento,
    
    -- Calcular edad basada en fecha de nacimiento
    TIMESTAMPDIFF(YEAR, u.fechaNacimiento, CURDATE()) as edad,
    
    -- Contar CVs del usuario
    COUNT(c.id) as total_cvs,
    
    -- Mostrar títulos de CVs como profesiones (separados por | para convertir a lista)
    CASE 
        WHEN COUNT(c.id) = 0 THEN 'Sin CVs registrados'
        ELSE GROUP_CONCAT(DISTINCT c.titulo ORDER BY c.titulo ASC SEPARATOR '|')
    END as profesiones,
    
    u.createdAt,
    u.updatedAt
    
FROM users u
LEFT JOIN cvs c ON u.id = c.userId AND c.deletedAt = 0
WHERE u.deletedAt = 0
GROUP BY u.id, u.nombre, u.apellidos, u.email, u.fechaNacimiento, u.createdAt, u.updatedAt;
