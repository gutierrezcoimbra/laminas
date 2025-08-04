<?php
// Script de prueba para verificar la base de datos
require_once 'vendor/autoload.php';

use Laminas\Db\Adapter\Adapter;

// Configuración de la base de datos (ajusta según tu configuración)
$config = [
    'driver' => 'Pdo_Mysql',
    'host' => 'localhost',
    'database' => 'laminastest',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'options' => [
        'buffer_results' => true,
    ],
];

try {
    $adapter = new Adapter($config);
    echo "✅ Conexión a la base de datos exitosa\n";
    
    // Verificar si la tabla users existe
    $result = $adapter->query("SHOW TABLES LIKE 'users'", Adapter::QUERY_MODE_EXECUTE);
    if ($result->count() > 0) {
        echo "✅ Tabla 'users' existe\n";
        
        // Verificar la estructura de la tabla
        $result = $adapter->query("DESCRIBE users", Adapter::QUERY_MODE_EXECUTE);
        echo "📋 Estructura de la tabla 'users':\n";
        foreach ($result as $row) {
            echo "  - {$row['Field']}: {$row['Type']} " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
        
        // Verificar si hay datos en la tabla
        $result = $adapter->query("SELECT COUNT(*) as count FROM users", Adapter::QUERY_MODE_EXECUTE);
        $count = $result->current()['count'];
        echo "📊 Número de usuarios en la tabla: $count\n";
        
    } else {
        echo "❌ Tabla 'users' no existe\n";
        echo "🔧 Creando tabla 'users'...\n";
        
        // Crear la tabla users
        $sql = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL,
            apellidos VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            fechaNacimiento DATE,
            deletedAt INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
        
        $adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
        echo "✅ Tabla 'users' creada exitosamente\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 