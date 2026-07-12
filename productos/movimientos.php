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

$stmt = $pdo->prepare("SELECT m.*, u.nombre_completo AS usuario_nombre
    FROM productos_movimientos m
    JOIN usuarios u ON u.id = m.usuario_id
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
        <div class="label">Stock actual</div>
        <div class="value"><?= h($producto['stock']) ?> <?= h($producto['unidad']) ?></div>
    </div>
    <div class="card">
        <div class="label">Costo unitario actual</div>
        <div class="value"><?= money($producto['costo_unitario']) ?></div>
    </div>
</div>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Costo unitario</th><th>Costo total</th><th>Motivo</th><th>Usuario</th></tr>
    </thead>
    <tbody>
    <?php foreach ($movimientos as $m): ?>
        <tr>
            <td><?= h(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
            <td><?= $m['tipo'] === 'entrada' ? '<span class="tag" style="background:#e8f5e9;color:#2e7d32;">Entrada</span>' : '<span class="tag" style="background:#fdecea;color:#c62828;">Salida</span>' ?></td>
            <td><?= h($m['cantidad']) ?></td>
            <td><?= money($m['costo_unitario']) ?></td>
            <td><?= money($m['costo_total']) ?></td>
            <td><?= h($m['motivo']) ?><?= $m['ingreso_id'] ? ' (Ingreso #' . (int)$m['ingreso_id'] . ')' : '' ?></td>
            <td><?= h($m['usuario_nombre']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$movimientos): ?>
        <tr><td colspan="7" class="muted">Sin movimientos registrados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
