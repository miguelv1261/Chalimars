<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Depositos';
$depositos = $pdo->query('SELECT * FROM depositos ORDER BY fecha DESC, id DESC')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Depositos</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>depositos/export.php">Exportar a Excel</a>
        <a class="btn" href="<?= BASE_URL ?>depositos/form.php">Nuevo deposito</a>
    </div>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Fecha</th><th>Banco</th><th>Referencia</th><th>Monto</th><th>Comprobante</th><?php if (is_admin()): ?><th></th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($depositos as $d): ?>
        <tr>
            <td><?= h($d['fecha']) ?></td>
            <td><?= h($d['banco']) ?></td>
            <td><?= h($d['numero_referencia']) ?></td>
            <td><?= money($d['monto']) ?></td>
            <td><?= $d['comprobante_archivo'] ? '<a href="' . BASE_URL . 'uploads/depositos/' . h($d['comprobante_archivo']) . '" target="_blank">Ver</a>' : '-' ?></td>
            <?php if (is_admin()): ?>
            <td>
                <form class="inline" method="post" action="<?= BASE_URL ?>depositos/delete.php" onsubmit="return confirm('Eliminar este deposito?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                    <button type="submit" class="btn-icon btn-icon-danger" title="Eliminar"><?= icon_svg('trash') ?></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$depositos): ?><tr><td colspan="6" class="muted">Sin depositos registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
