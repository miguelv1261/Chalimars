<?php
// Conexion PDO a la base de datos

// En hosting compartido donde no se pueden definir variables de entorno,
// cree config/database.local.php (ignorado por git) con las credenciales
// reales. Vea config/database.local.example.php como plantilla.
if (file_exists(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
}

$db_host = $db_host ?? (getenv('DB_HOST') ?: 'localhost');
$db_name = $db_name ?? (getenv('DB_NAME') ?: 'chalimars');
$db_user = $db_user ?? (getenv('DB_USER') ?: 'root');
$db_pass = $db_pass ?? (getenv('DB_PASS') ?: '');

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Error de conexion a la base de datos. Verifique la configuracion en config/database.php');
}
