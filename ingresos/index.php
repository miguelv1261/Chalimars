<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Ingresos';
$ingresos = $pdo->query("SELECT i.*, COALESCE(c.total_costo,0) total_costo
    FROM ingresos i
    LEFT JOIN (SELECT ingreso_id, SUM(costo_total) total_costo FROM ingresos_costos GROUP BY ingreso_id) c
        ON c.ingreso_id = i.id
    ORDER BY i.fecha DESC, i.id DESC")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Ingresos</h1>
    <a class="btn" href="<?= BASE_URL ?>ingresos/form.php">Nuevo ingreso</a>
</div>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Fecha</th><th>Cliente</th><th>Descripcion</th><th>Monto</th><th>Costo total</th><th>Utilidad</th><th>Factura</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($ingresos as $i): $utilidad = $i['monto'] - $i['total_costo']; ?>
        <tr>
            <td><?= h($i['fecha']) ?></td>
            <td><?= h($i['cliente']) ?></td>
            <td><?= h($i['descripcion']) ?></td>
            <td><?= money($i['monto']) ?></td>
            <td><?= money($i['total_costo']) ?></td>
            <td style="color: <?= $utilidad >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidad) ?></td>
            <td><?= $i['factura_pdf'] ? '<a href="' . BASE_URL . 'uploads/facturas/' . h($i['factura_pdf']) . '" target="_blank">PDF</a>' : '-' ?></td>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>ingresos/ver.php?id=<?= (int)$i['id'] ?>" title="Ver / Costear"><?= icon_svg('eye') ?></a>
                    <?php if (is_admin()): ?>
                        <a class="btn-icon" href="<?= BASE_URL ?>ingresos/form.php?id=<?= (int)$i['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$ingresos): ?><tr><td colspan="8" class="muted">Sin ingresos registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
