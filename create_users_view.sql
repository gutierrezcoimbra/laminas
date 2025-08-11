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
