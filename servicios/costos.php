<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'servicios/index.php');
}
csrf_verify();

$servicioId = (int)($_POST['servicio_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ?');
$stmt->execute([$servicioId]);
$servicio = $stmt->fetch();
if (!$servicio) {
    flash_set('Servicio no encontrado.', 'error');
    redirect(BASE_URL . 'servicios/index.php');
}

$tipo = $_POST['tipo_costo'] ?? '';
$cantidad = (float)($_POST['cantidad'] ?? 1);

if (!in_array($tipo, ['material', 'mano_obra', 'gasto_indirecto'], true) || $cantidad <= 0) {
    flash_set('Datos de costo invalidos.', 'error');
    redirect(BASE_URL . 'servicios/ver.php?id=' . $servicioId);
}

if ($tipo === 'material') {
    $productoId = (int)($_POST['producto_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id FROM productos WHERE id = ?');
    $stmt->execute([$productoId]);
    if (!$stmt->fetch()) {
        flash_set('Producto no encontrado.', 'error');
        redirect(BASE_URL . 'servicios/ver.php?id=' . $servicioId);
    }
    $pdo->prepare('INSERT INTO servicios_costos (servicio_id, tipo_costo, producto_id, cantidad) VALUES (?,?,?,?)')
        ->execute([$servicioId, 'material', $productoId, $cantidad]);

} elseif ($tipo === 'mano_obra') {
    $manoObraId = (int)($_POST['mano_obra_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id FROM mano_obra WHERE id = ?');
    $stmt->execute([$manoObraId]);
    if (!$stmt->fetch()) {
        flash_set('Mano de obra no encontrada.', 'error');
        redirect(BASE_URL . 'servicios/ver.php?id=' . $servicioId);
    }
    $pdo->prepare('INSERT INTO servicios_costos (servicio_id, tipo_costo, mano_obra_id, cantidad) VALUES (?,?,?,?)')
        ->execute([$servicioId, 'mano_obra', $manoObraId, $cantidad]);

} else {
    $gastoId = (int)($_POST['gasto_indirecto_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id FROM gastos_indirectos WHERE id = ?');
    $stmt->execute([$gastoId]);
    if (!$stmt->fetch()) {
        flash_set('Gasto indirecto no encontrado.', 'error');
        redirect(BASE_URL . 'servicios/ver.php?id=' . $servicioId);
    }
    $pdo->prepare('INSERT INTO servicios_costos (servicio_id, tipo_costo, gasto_indirecto_id, cantidad) VALUES (?,?,?,?)')
        ->execute([$servicioId, 'gasto_indirecto', $gastoId, $cantidad]);
}

flash_set('Linea agregada a la receta.');
redirect(BASE_URL . 'servicios/ver.php?id=' . $servicioId);
