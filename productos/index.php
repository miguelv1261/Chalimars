<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Inventario de materiales';
$productos = $pdo->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Inventario de materiales</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>productos/export.php">Exportar a Excel</a>
        <?php if (is_admin()): ?>
            <a class="btn" href="<?= BASE_URL ?>productos/form.php">Nuevo producto</a>
        <?php endif; ?>
    </div>
</div>
<p class="muted">El costeo se calcula por unidad de uso: precio de compra &divide; rendimiento = costo por uso (ej. shampoo de $15 con 20 usos = $0.75 por uso).</p>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr>
        <th>Codigo</th><th>Nombre</th>
        <th>Precio compra</th><th>Precio venta</th><th>Rendimiento</th><th>Costo por uso</th>
        <th>Stock tangible</th><th>Stock uso</th><th>Stock minimo</th><th data-filter>Estado</th><th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
        <tr>
            <td><?= h($p['codigo']) ?></td>
            <td><?= h($p['nombre']) ?></td>
            <td><?= money($p['precio_compra']) ?></td>
            <td><?= money($p['precio_venta_uso']) ?></td>
            <td><?= h($p['rendimiento']) ?></td>
            <td><?= money($p['costo_uso']) ?></td>
            <td><?= h($p['stock_tangible']) ?></td>
            <td<?= $p['stock_uso'] <= $p['stock_minimo'] ? ' style="color:#c4293a;font-weight:600;"' : '' ?>><?= h($p['stock_uso']) ?></td>
            <td><?= h($p['stock_minimo']) ?></td>
            <td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>productos/movimientos.php?id=<?= (int)$p['id'] ?>" title="Movimientos"><?= icon_svg('history') ?></a>
                    <?php if (is_admin()): ?>
                        <a class="btn-icon" href="<?= BASE_URL ?>productos/entrada.php?id=<?= (int)$p['id'] ?>" title="Entrada de stock"><?= icon_svg('plus-circle') ?></a>
                        <a class="btn-icon" href="<?= BASE_URL ?>productos/form.php?id=<?= (int)$p['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                        <form class="inline" method="post" action="<?= BASE_URL ?>productos/delete.php" onsubmit="return confirm('Cambiar estado de este producto?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn-icon <?= $p['activo'] ? 'btn-icon-danger' : 'btn-icon-success' ?>" title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icon_svg('power') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$productos): ?><tr><td colspan="11" class="muted">Sin productos registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
