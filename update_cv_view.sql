-- Actualizar la vista cv_datagrid_view con cambios solicitados:
-- 1. Quitar fecha creación
-- 2. Combinar nombre y email en una sola columna
-- 3. Truncar resumen a 150 caracteres

DROP VIEW IF EXISTS cv_datagrid_view;

CREATE VIEW cv_datagrid_view AS
SELECT 
    c.id as cv_id,
    c.titulo,
    -- Truncar resumen a 150 caracteres con '...' si es más largo
    CASE 
        WHEN LENGTH(c.resumen) > 150 
        THEN CONCAT(SUBSTRING(c.resumen, 1, 150), '...') 
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
