<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$pageTitle = 'Usuarios';
$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY nombre_completo')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Usuarios</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>usuarios/export.php">Exportar a Excel</a>
        <a class="btn" href="<?= BASE_URL ?>usuarios/form.php">Nuevo usuario</a>
    </div>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Nombre</th><th>Usuario</th><th data-filter>Rol</th><th data-filter>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= h($u['nombre_completo']) ?></td>
            <td><?= h($u['username']) ?></td>
            <td><?= h($u['rol']) ?></td>
            <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>usuarios/form.php?id=<?= (int)$u['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                    <?php if ($u['id'] != current_user()['id']): ?>
                    <form class="inline" method="post" action="<?= BASE_URL ?>usuarios/delete.php" onsubmit="return confirm('Desactivar este usuario?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn-icon <?= $u['activo'] ? 'btn-icon-danger' : 'btn-icon-success' ?>" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icon_svg('power') ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
