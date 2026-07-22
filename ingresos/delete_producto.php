<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'ingresos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$ingresoId = (int)($_POST['ingreso_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM ingresos_productos WHERE id = ? AND ingreso_id = ?');
$stmt->execute([$id, $ingresoId]);
$venta = $stmt->fetch();

if ($venta) {
    $pdo->beginTransaction();
    reponer_stock_producto(
        $pdo, (int)$venta['producto_id'], (float)$venta['cantidad'],
        'Reverso de venta eliminada - Ingreso #' . $ingresoId,
        $ingresoId, current_user()['id']
    );
    $pdo->prepare('DELETE FROM ingresos_productos WHERE id = ?')->execute([$id]);
    $pdo->commit();
    flash_set('Producto vendido eliminado y stock repuesto.');
}

redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
