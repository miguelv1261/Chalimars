<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Mano de obra / Servicios';
$servicios = $pdo->query('SELECT * FROM servicios ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Mano de obra / Servicios</h1>
    <?php if (is_admin()): ?>
        <a class="btn" href="<?= BASE_URL ?>servicios/form.php">Nuevo servicio</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Nombre</th><th>Descripcion</th><th>Costo mano de obra</th><th>Estado</th><?php if (is_admin()): ?><th>Acciones</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($servicios as $s): ?>
        <tr>
            <td><?= h($s['nombre']) ?></td>
            <td><?= h($s['descripcion']) ?></td>
            <td><?= money($s['costo_mano_obra']) ?></td>
            <td><?= $s['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <?php if (is_admin()): ?>
            <td class="actions">
                <a class="btn btn-sm" href="<?= BASE_URL ?>servicios/form.php?id=<?= (int)$s['id'] ?>">Editar</a>
                <form class="inline" method="post" action="<?= BASE_URL ?>servicios/delete.php" onsubmit="return confirm('Cambiar estado de este servicio?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><?= $s['activo'] ? 'Desactivar' : 'Activar' ?></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
