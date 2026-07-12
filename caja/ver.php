<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT cs.*, ua.nombre_completo AS abierta_por_nombre, uc.nombre_completo AS cerrada_por_nombre
    FROM cajas_sesiones cs
    JOIN usuarios ua ON ua.id = cs.abierta_por
    LEFT JOIN usuarios uc ON uc.id = cs.cerrada_por
    WHERE cs.id = ?");
$stmt->execute([$id]);
$sesion = $stmt->fetch();
if (!$sesion) {
    flash_set('Sesion de caja no encontrada.', 'error');
    redirect(BASE_URL . 'caja/index.php');
}

$ingresos = $pdo->prepare('SELECT * FROM ingresos WHERE caja_sesion_id = ? ORDER BY fecha, id');
$ingresos->execute([$id]);
$ingresos = $ingresos->fetchAll();

$egresos = $pdo->prepare('SELECT * FROM egresos WHERE caja_sesion_id = ? ORDER BY fecha, id');
$egresos->execute([$id]);
$egresos = $egresos->fetchAll();

$depositos = $pdo->prepare('SELECT * FROM depositos WHERE caja_sesion_id = ? ORDER BY fecha, id');
$depositos->execute([$id]);
$depositos = $depositos->fetchAll();

$totalIngresos = array_sum(array_column($ingresos, 'monto'));
$totalEgresos = array_sum(array_column($egresos, 'monto'));
$totalDepositos = array_sum(array_column($depositos, 'monto'));

$pageTitle = 'Detalle de caja';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Caja #<?= (int)$sesion['id'] ?></h1>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>caja/index.php">Volver</a>
</div>

<div class="summary-cards">
    <div class="card"><div class="label">Estado</div><div class="value" style="font-size:16px;"><?= $sesion['estado'] === 'abierta' ? '<span class="badge badge-open">Abierta</span>' : '<span class="badge badge-closed">Cerrada</span>' ?></div></div>
    <div class="card"><div class="label">Apertura</div><div class="value" style="font-size:16px;"><?= money($sesion['monto_apertura']) ?></div><div class="muted"><?= h(date('d/m/Y H:i', strtotime($sesion['fecha_apertura']))) ?> - <?= h($sesion['abierta_por_nombre']) ?></div></div>
    <div class="card"><div class="label">Ingresos</div><div class="value"><?= money($totalIngresos) ?></div></div>
    <div class="card"><div class="label">Egresos</div><div class="value"><?= money($totalEgresos) ?></div></div>
    <div class="card"><div class="label">Depositos</div><div class="value"><?= money($totalDepositos) ?></div></div>
    <?php if ($sesion['estado'] === 'cerrada'): ?>
    <div class="card"><div class="label">Contado / Esperado</div><div class="value" style="font-size:16px;"><?= money($sesion['monto_contado']) ?> / <?= money($sesion['monto_esperado']) ?></div><div class="muted">Diferencia: <?= money($sesion['diferencia']) ?></div></div>
    <?php endif; ?>
</div>

<div class="panel">
    <h2 class="mt-0">Ingresos</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Descripcion</th><th>Monto</th><th>Factura</th></tr></thead>
        <tbody>
        <?php foreach ($ingresos as $i): ?>
            <tr>
                <td><?= h($i['fecha']) ?></td>
                <td><?= h($i['cliente']) ?></td>
                <td><a href="<?= BASE_URL ?>ingresos/ver.php?id=<?= (int)$i['id'] ?>"><?= h($i['descripcion']) ?></a></td>
                <td><?= money($i['monto']) ?></td>
                <td><?= $i['factura_pdf'] ? '<a href="' . BASE_URL . 'uploads/facturas/' . h($i['factura_pdf']) . '" target="_blank">Ver PDF</a>' : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$ingresos): ?><tr><td colspan="5" class="muted">Sin ingresos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Egresos</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Fecha</th><th>Descripcion</th><th>Documento</th><th>Monto</th><th>Archivo</th></tr></thead>
        <tbody>
        <?php foreach ($egresos as $e): ?>
            <tr>
                <td><?= h($e['fecha']) ?></td>
                <td><?= h($e['descripcion']) ?></td>
                <td><?= $e['tipo_documento'] === 'factura' ? 'Factura' : 'Nota de venta' ?> <?= h($e['numero_documento']) ?></td>
                <td><?= money($e['monto']) ?></td>
                <td><?= $e['documento_archivo'] ? '<a href="' . BASE_URL . 'uploads/egresos/' . h($e['documento_archivo']) . '" target="_blank">Ver</a>' : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$egresos): ?><tr><td colspan="5" class="muted">Sin egresos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Depositos</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Fecha</th><th>Banco</th><th>Referencia</th><th>Monto</th><th>Comprobante</th></tr></thead>
        <tbody>
        <?php foreach ($depositos as $d): ?>
            <tr>
                <td><?= h($d['fecha']) ?></td>
                <td><?= h($d['banco']) ?></td>
                <td><?= h($d['numero_referencia']) ?></td>
                <td><?= money($d['monto']) ?></td>
                <td><?= $d['comprobante_archivo'] ? '<a href="' . BASE_URL . 'uploads/depositos/' . h($d['comprobante_archivo']) . '" target="_blank">Ver</a>' : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$depositos): ?><tr><td colspan="5" class="muted">Sin depositos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
