<?php
// Control de sesion y roles. Debe incluirse despues de config.php y database.php

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect(BASE_URL . 'login.php');
    }
}

/**
 * Restringe la pagina a los roles indicados. Ej: require_role(['admin'])
 */
function require_role(array $roles) {
    require_login();
    $user = current_user();
    if (!in_array($user['rol'], $roles, true)) {
        http_response_code(403);
        require BASE_PATH . '/includes/403.php';
        exit;
    }
}

/**
 * Devuelve true para 'admin' (el contador, uso operativo diario del
 * sistema) y para 'desarrollador' (soporte/programacion, con el mismo
 * acceso completo de administracion). El rol 'cajero' ya no se usa.
 */
function is_admin() {
    $user = current_user();
    return $user && in_array($user['rol'], ['admin', 'desarrollador'], true);
}

/**
 * Devuelve true solo para el rol 'desarrollador' (soporte/programacion),
 * usado para el modulo de Requerimientos.
 */
function is_dev() {
    $user = current_user();
    return $user && $user['rol'] === 'desarrollador';
}

/**
 * Restringe la pagina a los roles de administracion del sistema (admin o
 * desarrollador): equivalente a require_role(['admin', 'desarrollador']),
 * usado por todo el CRUD operativo (productos, servicios, ingresos, etc.).
 */
function require_admin() {
    require_role(['admin', 'desarrollador']);
}

/**
 * Devuelve la sesion de caja actualmente abierta, o null si no hay ninguna.
 */
function caja_abierta(PDO $pdo) {
    $stmt = $pdo->query("SELECT * FROM cajas_sesiones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
    return $stmt->fetch() ?: null;
}

/**
 * Exige que haya una caja abierta antes de continuar; si no, redirige con mensaje.
 */
function require_caja_abierta(PDO $pdo) {
    $sesion = caja_abierta($pdo);
    if (!$sesion) {
        flash_set('Debe abrir una caja antes de registrar movimientos.', 'error');
        redirect(BASE_URL . 'caja/index.php');
    }
    return $sesion;
}
