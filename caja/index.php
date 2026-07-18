<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Sesiones de caja';
$sesiones = $pdo->query("SELECT cs.*, ua.nombre_completo AS abierta_por_nombre, uc.nombre_completo AS cerrada_por_nombre
    FROM cajas_sesiones cs
    JOIN usuarios ua ON ua.id = cs.abierta_por
    LEFT JOIN usuarios uc ON uc.id = cs.cerrada_por
    ORDER BY cs.id DESC")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Sesiones de caja</h1>
    <?php $abierta = caja_abierta($pdo); ?>
    <?php if (!$abierta): ?>
        <a class="btn" href="<?= BASE_URL ?>caja/abrir.php">Abrir caja</a>
    <?php else: ?>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>caja/cerrar.php">Cerrar caja actual</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>#</th><th>Apertura</th><th>Cierre</th><th>Monto apertura</th><th>Monto contado</th><th>Diferencia</th><th>Estado</th><th>Abierta por</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($sesiones as $s): ?>
        <tr>
            <td>#<?= (int)$s['id'] ?></td>
            <td><?= h(date('d/m/Y H:i', strtotime($s['fecha_apertura']))) ?></td>
            <td><?= $s['fecha_cierre'] ? h(date('d/m/Y H:i', strtotime($s['fecha_cierre']))) : '-' ?></td>
            <td><?= money($s['monto_apertura']) ?></td>
            <td><?= $s['monto_contado'] !== null ? money($s['monto_contado']) : '-' ?></td>
            <td><?= $s['diferencia'] !== null ? money($s['diferencia']) : '-' ?></td>
            <td><?= $s['estado'] === 'abierta' ? '<span class="badge badge-open">Abierta</span>' : '<span class="badge badge-closed">Cerrada</span>' ?></td>
            <td><?= h($s['abierta_por_nombre']) ?></td>
            <td><a class="btn-icon" href="<?= BASE_URL ?>caja/ver.php?id=<?= (int)$s['id'] ?>" title="Ver detalle"><?= icon_svg('eye') ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
