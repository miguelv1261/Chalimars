<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Inventario de materiales';
$productos = $pdo->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Inventario de materiales</h1>
    <?php if (is_admin()): ?>
        <a class="btn" href="<?= BASE_URL ?>productos/form.php">Nuevo producto</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Codigo</th><th>Nombre</th><th>Unidad</th><th>Costo unitario</th><th>Stock</th><th>Stock minimo</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
        <tr>
            <td><?= h($p['codigo']) ?></td>
            <td><?= h($p['nombre']) ?></td>
            <td><?= h($p['unidad']) ?></td>
            <td><?= money($p['costo_unitario']) ?></td>
            <td<?= $p['stock'] <= $p['stock_minimo'] ? ' style="color:#c62828;font-weight:600;"' : '' ?>><?= h($p['stock']) ?></td>
            <td><?= h($p['stock_minimo']) ?></td>
            <td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td class="actions">
                <a class="btn btn-sm" href="<?= BASE_URL ?>productos/movimientos.php?id=<?= (int)$p['id'] ?>">Movimientos</a>
                <?php if (is_admin()): ?>
                    <a class="btn btn-sm" href="<?= BASE_URL ?>productos/entrada.php?id=<?= (int)$p['id'] ?>">Entrada stock</a>
                    <a class="btn btn-sm" href="<?= BASE_URL ?>productos/form.php?id=<?= (int)$p['id'] ?>">Editar</a>
                    <form class="inline" method="post" action="<?= BASE_URL ?>productos/delete.php" onsubmit="return confirm('Cambiar estado de este producto?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
