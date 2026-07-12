<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$pageTitle = 'Usuarios';
$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY nombre_completo')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Usuarios</h1>
    <a class="btn" href="<?= BASE_URL ?>usuarios/form.php">Nuevo usuario</a>
</div>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= h($u['nombre_completo']) ?></td>
            <td><?= h($u['username']) ?></td>
            <td><?= h($u['rol']) ?></td>
            <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td class="actions">
                <a class="btn btn-sm" href="<?= BASE_URL ?>usuarios/form.php?id=<?= (int)$u['id'] ?>">Editar</a>
                <?php if ($u['id'] != current_user()['id']): ?>
                <form class="inline" method="post" action="<?= BASE_URL ?>usuarios/delete.php" onsubmit="return confirm('Desactivar este usuario?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
