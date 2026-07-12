<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'usuarios/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id && $id !== current_user()['id']) {
    $stmt = $pdo->prepare('UPDATE usuarios SET activo = NOT activo WHERE id = ?');
    $stmt->execute([$id]);
    flash_set('Estado del usuario actualizado.');
}
redirect(BASE_URL . 'usuarios/index.php');
