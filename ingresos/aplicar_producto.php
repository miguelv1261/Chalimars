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
    flash_set('Debe seleccionar un producto.', 'error');
    redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
}

try {
    aplicar_producto_a_ingreso($pdo, $productoId, $ingresoId, $cantidad, current_user()['id']);
    flash_set('Producto vendido y descontado del inventario correctamente.');
} catch (RuntimeException $e) {
    flash_set('No se pudo vender el producto: ' . $e->getMessage(), 'error');
}

redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
