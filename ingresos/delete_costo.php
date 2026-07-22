<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'ingresos/index.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$ingresoId = (int)($_POST['ingreso_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM ingresos_costos WHERE id = ? AND ingreso_id = ?');
$stmt->execute([$id, $ingresoId]);
$costo = $stmt->fetch();

if ($costo) {
    $pdo->beginTransaction();
    if ($costo['tipo_costo'] === 'material' && $costo['producto_id']) {
        reponer_stock_producto(
            $pdo, (int)$costo['producto_id'], (float)$costo['cantidad'],
            'Reverso de costo eliminado - Ingreso #' . $ingresoId,
            $ingresoId, current_user()['id']
        );
    }
    $pdo->prepare('DELETE FROM ingresos_costos WHERE id = ?')->execute([$id]);
    $pdo->commit();
    flash_set('Costo eliminado correctamente.');
}

redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
