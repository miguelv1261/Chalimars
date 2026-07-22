<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Mano de obra';
$manoObra = $pdo->query('SELECT * FROM mano_obra ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Mano de obra</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>mano_obra/export.php">Exportar a Excel</a>
        <?php if (is_admin()): ?>
            <a class="btn" href="<?= BASE_URL ?>mano_obra/form.php">Nueva tarifa</a>
        <?php endif; ?>
    </div>
</div>
<p class="muted">Catalogo de tarifas de trabajo que se usan al armar la receta de costeo de cada servicio.</p>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Nombre</th><th>Descripcion</th><th>Costo</th><th data-filter>Estado</th><?php if (is_admin()): ?><th>Acciones</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($manoObra as $m): ?>
        <tr>
            <td><?= h($m['nombre']) ?></td>
            <td><?= h($m['descripcion']) ?></td>
            <td><?= money($m['costo']) ?></td>
            <td><?= $m['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <?php if (is_admin()): ?>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>mano_obra/form.php?id=<?= (int)$m['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                    <form class="inline" method="post" action="<?= BASE_URL ?>mano_obra/delete.php" onsubmit="return confirm('Cambiar estado de esta tarifa?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn-icon <?= $m['activo'] ? 'btn-icon-danger' : 'btn-icon-success' ?>" title="<?= $m['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icon_svg('power') ?></button>
                    </form>
                </div>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$manoObra): ?><tr><td colspan="5" class="muted">Sin tarifas registradas.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
