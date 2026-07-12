<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'gastos_indirectos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('UPDATE gastos_indirectos SET activo = NOT activo WHERE id = ?')->execute([$id]);
    flash_set('Estado actualizado.');
}
redirect(BASE_URL . 'gastos_indirectos/index.php');
