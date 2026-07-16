<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Mano de obra';
$manoObra = $pdo->query('SELECT * FROM mano_obra ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Mano de obra</h1>
    <?php if (is_admin()): ?>
        <a class="btn" href="<?= BASE_URL ?>mano_obra/form.php">Nueva tarifa</a>
    <?php endif; ?>
</div>
<p class="muted">Catalogo de tarifas de trabajo que se usan al armar la receta de costeo de cada servicio.</p>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Nombre</th><th>Descripcion</th><th>Costo</th><th>Estado</th><?php if (is_admin()): ?><th>Acciones</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($manoObra as $m): ?>
        <tr>
            <td><?= h($m['nombre']) ?></td>
            <td><?= h($m['descripcion']) ?></td>
            <td><?= money($m['costo']) ?></td>
            <td><?= $m['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <?php if (is_admin()): ?>
            <td class="actions">
                <a class="btn btn-sm" href="<?= BASE_URL ?>mano_obra/form.php?id=<?= (int)$m['id'] ?>">Editar</a>
                <form class="inline" method="post" action="<?= BASE_URL ?>mano_obra/delete.php" onsubmit="return confirm('Cambiar estado de esta tarifa?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><?= $m['activo'] ? 'Desactivar' : 'Activar' ?></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$manoObra): ?><tr><td colspan="5" class="muted">Sin tarifas registradas.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
