<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$pageTitle = 'Proveedores';
$proveedores = $pdo->query('SELECT * FROM proveedores ORDER BY nombre')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Proveedores</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>proveedores/export.php">Exportar a Excel</a>
        <a class="btn" href="<?= BASE_URL ?>proveedores/form.php">Nuevo proveedor</a>
    </div>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Nombre</th><th>Contacto</th><th>Telefono</th><th>Email</th><th data-filter>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($proveedores as $p): ?>
        <tr>
            <td><?= h($p['nombre']) ?></td>
            <td><?= h($p['contacto']) ?></td>
            <td><?= h($p['telefono']) ?></td>
            <td><?= h($p['email']) ?></td>
            <td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>proveedores/form.php?id=<?= (int)$p['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                    <form class="inline" method="post" action="<?= BASE_URL ?>proveedores/delete.php" onsubmit="return confirm('Cambiar estado de este proveedor?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn-icon <?= $p['activo'] ? 'btn-icon-danger' : 'btn-icon-success' ?>" title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icon_svg('power') ?></button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$proveedores): ?><tr><td colspan="6" class="muted">Sin proveedores registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
