<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
if (!is_dev()) {
    http_response_code(403);
    require BASE_PATH . '/includes/403.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'requerimientos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$respuesta = trim($_POST['respuesta'] ?? '');
$estadosValidos = ['pendiente', 'en_progreso', 'completado', 'rechazado'];

if (!in_array($estado, $estadosValidos, true)) {
    flash_set('Estado invalido.', 'error');
    redirect(BASE_URL . 'requerimientos/ver.php?id=' . $id);
}

$stmt = $pdo->prepare('SELECT id FROM requerimientos WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    flash_set('Requerimiento no encontrado.', 'error');
    redirect(BASE_URL . 'requerimientos/index.php');
}

$pdo->prepare('UPDATE requerimientos SET estado=?, respuesta=? WHERE id=?')
    ->execute([$estado, $respuesta !== '' ? $respuesta : null, $id]);

flash_set('Requerimiento actualizado correctamente.');
redirect(BASE_URL . 'requerimientos/ver.php?id=' . $id);
