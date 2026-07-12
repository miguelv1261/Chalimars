<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'depositos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('DELETE FROM depositos WHERE id = ?')->execute([$id]);
    flash_set('Deposito eliminado.');
}
redirect(BASE_URL . 'depositos/index.php');
