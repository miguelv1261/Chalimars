<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'ingresos/index.php');
}
csrf_verify();

$ingresoId = (int)($_POST['ingreso_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM ingresos WHERE id = ?');
$stmt->execute([$ingresoId]);
$ingreso = $stmt->fetch();
if (!$ingreso) {
    flash_set('Ingreso no encontrado.', 'error');
    redirect(BASE_URL . 'ingresos/index.php');
}

$tipo = $_POST['tipo_costo'] ?? '';
$cantidad = (float)($_POST['cantidad'] ?? 1);

if (!in_array($tipo, ['material', 'mano_obra', 'gasto_indirecto'], true) || $cantidad <= 0) {
    flash_set('Datos de costo invalidos.', 'error');
    redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
}

try {
    $pdo->beginTransaction();

    if ($tipo === 'material') {
        $productoId = (int)($_POST['producto_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? FOR UPDATE');
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch();
        if (!$producto) {
            throw new RuntimeException('Producto no encontrado.');
        }
        if ($producto['stock'] < $cantidad) {
            throw new RuntimeException('Stock insuficiente. Disponible: ' . $producto['stock'] . ' ' . $producto['unidad']);
        }
        $costoUnitario = (float)$producto['costo_unitario'];
        $costoTotal = round($cantidad * $costoUnitario, 2);

        $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, producto_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?)')
            ->execute([$ingresoId, 'material', $productoId, $cantidad, $costoUnitario, $costoTotal, current_user()['id']]);

        $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?')->execute([$cantidad, $productoId]);

        $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, motivo, ingreso_id, usuario_id) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$productoId, 'salida', $cantidad, $costoUnitario, $costoTotal, 'Uso en ingreso #' . $ingresoId, $ingresoId, current_user()['id']]);

    } elseif ($tipo === 'mano_obra') {
        $servicioId = (int)($_POST['servicio_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ?');
        $stmt->execute([$servicioId]);
        $servicio = $stmt->fetch();
        if (!$servicio) {
            throw new RuntimeException('Servicio no encontrado.');
        }
        $costoUnitario = (float)$servicio['costo_mano_obra'];
        $costoTotal = round($cantidad * $costoUnitario, 2);

        $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?)')
            ->execute([$ingresoId, 'mano_obra', $servicioId, $cantidad, $costoUnitario, $costoTotal, current_user()['id']]);

    } else { // gasto_indirecto
        $gastoId = (int)($_POST['gasto_indirecto_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM gastos_indirectos WHERE id = ?');
        $stmt->execute([$gastoId]);
        $gasto = $stmt->fetch();
        if (!$gasto) {
            throw new RuntimeException('Gasto indirecto no encontrado.');
        }
        $costoUnitario = (float)$gasto['costo_unitario'];
        $costoTotal = round($cantidad * $costoUnitario, 2);

        $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, gasto_indirecto_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?)')
            ->execute([$ingresoId, 'gasto_indirecto', $gastoId, $cantidad, $costoUnitario, $costoTotal, current_user()['id']]);
    }

    $pdo->commit();
    flash_set('Costo agregado correctamente.');
} catch (RuntimeException $e) {
    $pdo->rollBack();
    flash_set($e->getMessage(), 'error');
}

redirect(BASE_URL . 'ingresos/ver.php?id=' . $ingresoId);
