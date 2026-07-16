<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'proveedores/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('UPDATE proveedores SET activo = NOT activo WHERE id = ?')->execute([$id]);
    flash_set('Estado del proveedor actualizado.');
}
redirect(BASE_URL . 'proveedores/index.php');
