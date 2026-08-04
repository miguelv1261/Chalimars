<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'ingresos/index.php');
}
csrf_verify();

$ingresoId = (int)($_POST['ingreso_id'] ?? 0);
$productoId = (int)($_POST['producto_id'] ?? 0);
$cantidad = (float)($_POST['cantidad'] ?? 1) ?: 1;

$stmt = $pdo->prepare('SELECT id FROM ingresos WHERE id = ?');
$stmt->execute([$ingresoId]);
if (!$stmt->fetch()) {
    flash_set('Ingreso no encontrado.', 'error');
    redirect(BASE_URL . 'ingresos/index.php');
}

if (!$productoId) {
    flash_set('Debe seleccionar un material.', 'error');
    redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
}

try {
    agregar_material_a_ingreso($pdo, $productoId, $ingresoId, $cantidad, current_user()['id']);
    flash_set('Material agregado al costeo correctamente.');
} catch (RuntimeException $e) {
    flash_set('No se pudo agregar el material: ' . $e->getMessage(), 'error');
}

redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
