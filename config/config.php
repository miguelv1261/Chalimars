<?php
// Configuracion general del sistema

error_reporting(E_ALL);
ini_set('display_errors', '0'); // poner en '1' solo en desarrollo

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $isHttps,
    ]);
}

define('BASE_PATH', dirname(__DIR__));

// Calcula BASE_URL automaticamente a partir de la carpeta del proyecto,
// para que funcione tanto en la raiz del servidor como en una subcarpeta
// (ej. http://localhost/Chalimars/ dentro de htdocs).
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projectRoot = rtrim(str_replace('\\', '/', BASE_PATH), '/');
$basePath = ($documentRoot !== '' && str_starts_with($projectRoot, $documentRoot))
    ? substr($projectRoot, strlen($documentRoot))
    : '';
define('BASE_URL', $basePath === '' ? '/' : $basePath . '/');

define('UPLOAD_FACTURAS', BASE_PATH . '/uploads/facturas/');
define('UPLOAD_EGRESOS', BASE_PATH . '/uploads/egresos/');
define('UPLOAD_DEPOSITOS', BASE_PATH . '/uploads/depositos/');
define('UPLOAD_BRANDING', BASE_PATH . '/uploads/branding/');

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
