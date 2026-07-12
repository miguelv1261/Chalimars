<?php
// Configuracion general del sistema

error_reporting(E_ALL);
ini_set('display_errors', '0'); // poner en '1' solo en desarrollo

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/Chalimar/'); // ajustar si la app vive en un subdirectorio

define('UPLOAD_FACTURAS', BASE_PATH . '/uploads/facturas/');
define('UPLOAD_EGRESOS', BASE_PATH . '/uploads/egresos/');
define('UPLOAD_DEPOSITOS', BASE_PATH . '/uploads/depositos/');

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
