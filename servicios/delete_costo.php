<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'servicios/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$servicioId = (int)($_POST['servicio_id'] ?? 0);

$pdo->prepare('DELETE FROM servicios_costos WHERE id = ? AND servicio_id = ?')->execute([$id, $servicioId]);
flash_set('Linea eliminada de la receta.');
redirect(BASE_URL . 'servicios/ver.php?id=' . $servicioId);
