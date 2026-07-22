<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Gastos indirectos';
$gastos = $pdo->query('SELECT * FROM gastos_indirectos ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Gastos indirectos</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>gastos_indirectos/export.php">Exportar a Excel</a>
        <?php if (is_admin()): ?>
            <a class="btn" href="<?= BASE_URL ?>gastos_indirectos/form.php">Nuevo gasto indirecto</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Nombre</th><th>Descripcion</th><th>Costo unitario</th><th data-filter>Estado</th><?php if (is_admin()): ?><th>Acciones</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($gastos as $g): ?>
        <tr>
            <td><?= h($g['nombre']) ?></td>
            <td><?= h($g['descripcion']) ?></td>
            <td><?= money($g['costo_unitario']) ?></td>
            <td><?= $g['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <?php if (is_admin()): ?>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>gastos_indirectos/form.php?id=<?= (int)$g['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                    <form class="inline" method="post" action="<?= BASE_URL ?>gastos_indirectos/delete.php" onsubmit="return confirm('Cambiar estado de este gasto?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                        <button type="submit" class="btn-icon <?= $g['activo'] ? 'btn-icon-danger' : 'btn-icon-success' ?>" title="<?= $g['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icon_svg('power') ?></button>
                    </form>
                </div>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
