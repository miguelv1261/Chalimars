<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Panel';
$sesion = caja_abierta($pdo);

$hoy = date('Y-m-d');

$ingresosHoy = $pdo->prepare('SELECT COALESCE(SUM(monto),0) total, COUNT(*) cantidad FROM ingresos WHERE fecha = ?');
$ingresosHoy->execute([$hoy]);
$ingresosHoy = $ingresosHoy->fetch();

$egresosHoy = $pdo->prepare('SELECT COALESCE(SUM(monto),0) total, COUNT(*) cantidad FROM egresos WHERE fecha = ?');
$egresosHoy->execute([$hoy]);
$egresosHoy = $egresosHoy->fetch();

$depositosHoy = $pdo->prepare('SELECT COALESCE(SUM(monto),0) total, COUNT(*) cantidad FROM depositos WHERE fecha = ?');
$depositosHoy->execute([$hoy]);
$depositosHoy = $depositosHoy->fetch();

$stockBajo = $pdo->query('SELECT * FROM productos WHERE activo = 1 AND stock <= stock_minimo ORDER BY nombre')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h1>Panel general</h1>
</div>

<div class="summary-cards">
    <div class="card">
        <div class="label">Estado de caja</div>
        <div class="value" style="font-size:16px;">
            <?php if ($sesion): ?>
                <span class="badge badge-open">Abierta</span>
            <?php else: ?>
                <span class="badge badge-closed">Cerrada</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="label">Ingresos de hoy</div>
        <div class="value"><?= money($ingresosHoy['total']) ?></div>
        <div class="muted"><?= (int)$ingresosHoy['cantidad'] ?> registro(s)</div>
    </div>
    <div class="card">
        <div class="label">Egresos de hoy</div>
        <div class="value"><?= money($egresosHoy['total']) ?></div>
        <div class="muted"><?= (int)$egresosHoy['cantidad'] ?> registro(s)</div>
    </div>
    <div class="card">
        <div class="label">Depositos de hoy</div>
        <div class="value"><?= money($depositosHoy['total']) ?></div>
        <div class="muted"><?= (int)$depositosHoy['cantidad'] ?> registro(s)</div>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Accesos rapidos</h2>
    <div class="actions">
        <?php if (!$sesion): ?>
            <a class="btn" href="<?= BASE_URL ?>caja/abrir.php">Abrir caja</a>
        <?php else: ?>
            <a class="btn" href="<?= BASE_URL ?>ingresos/form.php">Registrar ingreso</a>
            <a class="btn" href="<?= BASE_URL ?>egresos/form.php">Registrar egreso</a>
            <a class="btn" href="<?= BASE_URL ?>depositos/form.php">Registrar deposito</a>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>caja/cerrar.php">Cerrar caja</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($stockBajo): ?>
<div class="panel">
    <h2 class="mt-0">Alerta de inventario bajo</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Producto</th><th>Stock actual</th><th>Stock minimo</th></tr></thead>
        <tbody>
        <?php foreach ($stockBajo as $p): ?>
            <tr>
                <td><?= h($p['nombre']) ?></td>
                <td><?= h($p['stock']) ?> <?= h($p['unidad_uso']) ?></td>
                <td><?= h($p['stock_minimo']) ?> <?= h($p['unidad_uso']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
