<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'requerimientos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM requerimientos WHERE id = ?');
$stmt->execute([$id]);
$requerimiento = $stmt->fetch();

if (!$requerimiento) {
    flash_set('Requerimiento no encontrado.', 'error');
    redirect(BASE_URL . 'requerimientos/index.php');
}

$puedeEliminar = is_dev() || ($requerimiento['creado_por'] == current_user()['id'] && $requerimiento['estado'] === 'pendiente');
if (!$puedeEliminar) {
    flash_set('No tiene permiso para eliminar este requerimiento.', 'error');
    redirect(BASE_URL . 'requerimientos/ver.php?id=' . $id);
}

$pdo->prepare('DELETE FROM requerimientos WHERE id = ?')->execute([$id]);
flash_set('Requerimiento eliminado correctamente.');
redirect(BASE_URL . 'requerimientos/index.php');
