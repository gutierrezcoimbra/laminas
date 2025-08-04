<?php
// Script para crear la base de datos
require_once 'vendor/autoload.php';

use Laminas\Db\Adapter\Adapter;

// Configuración para conectar sin especificar base de datos
$config = [
    'driver' => 'Pdo_Mysql',
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
];

try {
    $adapter = new Adapter($config);
    echo "✅ Conexión a MySQL exitosa\n";
    
    // Crear la base de datos
    $adapter->query("CREATE DATABASE IF NOT EXISTS laminas_app CHARACTER SET utf8 COLLATE utf8_general_ci", Adapter::QUERY_MODE_EXECUTE);
    echo "✅ Base de datos 'laminas_app' creada o ya existe\n";
    
    // Conectar a la base de datos específica
    $config['database'] = 'laminas_app';
    $adapter = new Adapter($config);
    
    // Crear la tabla users
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
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
    
    // Verificar la estructura
    $result = $adapter->query("DESCRIBE users", Adapter::QUERY_MODE_EXECUTE);
    echo "📋 Estructura de la tabla 'users':\n";
    foreach ($result as $row) {
        echo "  - {$row['Field']}: {$row['Type']} " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    
    echo "🎉 Base de datos configurada correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 