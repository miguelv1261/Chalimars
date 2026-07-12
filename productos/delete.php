<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'productos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('UPDATE productos SET activo = NOT activo WHERE id = ?')->execute([$id]);
    flash_set('Estado del producto actualizado.');
}
redirect(BASE_URL . 'productos/index.php');
