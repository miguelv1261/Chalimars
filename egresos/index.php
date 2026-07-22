<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Egresos';
$egresos = $pdo->query('SELECT * FROM egresos ORDER BY fecha DESC, id DESC')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Egresos</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>egresos/export.php">Exportar a Excel</a>
        <a class="btn" href="<?= BASE_URL ?>egresos/form.php">Nuevo egreso</a>
    </div>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Fecha</th><th>Descripcion</th><th>Documento</th><th>Monto</th><th>Archivo</th><?php if (is_admin()): ?><th></th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($egresos as $e): ?>
        <tr>
            <td><?= h($e['fecha']) ?></td>
            <td><?= h($e['descripcion']) ?></td>
            <td><?= $e['tipo_documento'] === 'factura' ? 'Factura' : 'Nota de venta' ?> <?= h($e['numero_documento']) ?></td>
            <td><?= money($e['monto']) ?></td>
            <td><?= $e['documento_archivo'] ? '<a href="' . BASE_URL . 'uploads/egresos/' . h($e['documento_archivo']) . '" target="_blank">Ver</a>' : '-' ?></td>
            <?php if (is_admin()): ?>
            <td>
                <form class="inline" method="post" action="<?= BASE_URL ?>egresos/delete.php" onsubmit="return confirm('Eliminar este egreso?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                    <button type="submit" class="btn-icon btn-icon-danger" title="Eliminar"><?= icon_svg('trash') ?></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$egresos): ?><tr><td colspan="6" class="muted">Sin egresos registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
