<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$id]);
$producto = $stmt->fetch();
if (!$producto) {
    flash_set('Producto no encontrado.', 'error');
    redirect(BASE_URL . 'productos/index.php');
}

$stmt = $pdo->prepare("SELECT m.*, u.nombre_completo AS usuario_nombre, pr.nombre AS proveedor_nombre
    FROM productos_movimientos m
    JOIN usuarios u ON u.id = m.usuario_id
    LEFT JOIN proveedores pr ON pr.id = m.proveedor_id
    WHERE m.producto_id = ? ORDER BY m.created_at DESC, m.id DESC");
$stmt->execute([$id]);
$movimientos = $stmt->fetchAll();

$pageTitle = 'Movimientos de inventario';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Movimientos: <?= h($producto['nombre']) ?></h1>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>productos/index.php">Volver</a>
</div>

<div class="summary-cards">
    <div class="card">
        <div class="label">Stock tangible</div>
        <div class="value"><?= h($producto['stock_tangible']) ?></div>
    </div>
    <div class="card">
        <div class="label">Stock de uso</div>
        <div class="value"><?= h($producto['stock_uso']) ?></div>
    </div>
    <div class="card">
        <div class="label">Costo por uso actual</div>
        <div class="value"><?= money($producto['costo_uso']) ?></div>
    </div>
    <div class="card">
        <div class="label">Precio de compra actual</div>
        <div class="value"><?= money($producto['precio_compra']) ?></div>
    </div>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Fecha</th><th data-filter>Tipo</th><th>Cantidad (uso)</th><th>Compra</th><th>Proveedor</th><th>Costo unitario</th><th>Costo total</th><th>Motivo</th><th>Usuario</th></tr>
    </thead>
    <tbody>
    <?php foreach ($movimientos as $m): ?>
        <tr>
            <td><?= h(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
            <td><?= $m['tipo'] === 'entrada' ? '<span class="tag" style="background:#e6f5ea;color:#1e7d3c;">Entrada</span>' : '<span class="tag" style="background:#fbe9eb;color:#c4293a;">Salida</span>' ?></td>
            <td><?= h($m['cantidad']) ?></td>
            <td><?= $m['cantidad_compra'] !== null ? h($m['cantidad_compra']) . ' @ ' . money($m['precio_compra_unitario']) : '-' ?></td>
            <td><?= h($m['proveedor_nombre'] ?? '-') ?><?= $m['numero_documento'] ? ' (' . h($m['numero_documento']) . ')' : '' ?></td>
            <td><?= money($m['costo_unitario']) ?></td>
            <td><?= money($m['costo_total']) ?></td>
            <td><?= h($m['motivo']) ?><?= $m['ingreso_id'] ? ' (Ingreso #' . (int)$m['ingreso_id'] . ')' : '' ?></td>
            <td><?= h($m['usuario_nombre']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$movimientos): ?>
        <tr><td colspan="9" class="muted">Sin movimientos registrados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
