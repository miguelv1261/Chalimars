<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$hoy = date('Y-m-d');
$fechaDesde = $_GET['desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['hasta'] ?? $hoy;
if ($fechaDesde > $fechaHasta) {
    [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
}

// ---------- Libro diario: totales por dia ----------
$dias = [];

function libro_diario_acumular(&$dias, $filas, $campo) {
    foreach ($filas as $fila) {
        $fecha = $fila['fecha'];
        if (!isset($dias[$fecha])) {
            $dias[$fecha] = ['fecha' => $fecha, 'ventas' => 0, 'depositos' => 0, 'egresos' => 0, 'costo' => 0];
        }
        $dias[$fecha][$campo] = (float)$fila['total'];
    }
}

$stmt = $pdo->prepare('SELECT fecha, SUM(monto) total FROM ingresos WHERE fecha BETWEEN ? AND ? GROUP BY fecha');
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular($dias, $stmt->fetchAll(), 'ventas');

$stmt = $pdo->prepare('SELECT fecha, SUM(monto) total FROM depositos WHERE fecha BETWEEN ? AND ? GROUP BY fecha');
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular($dias, $stmt->fetchAll(), 'depositos');

$stmt = $pdo->prepare('SELECT fecha, SUM(monto) total FROM egresos WHERE fecha BETWEEN ? AND ? GROUP BY fecha');
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular($dias, $stmt->fetchAll(), 'egresos');

$stmt = $pdo->prepare("SELECT i.fecha, SUM(ic.costo_total) total
    FROM ingresos_costos ic JOIN ingresos i ON i.id = ic.ingreso_id
    WHERE i.fecha BETWEEN ? AND ? GROUP BY i.fecha");
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular($dias, $stmt->fetchAll(), 'costo');

krsort($dias);

$totalVentas = array_sum(array_column($dias, 'ventas'));
$totalDepositos = array_sum(array_column($dias, 'depositos'));
$totalEgresos = array_sum(array_column($dias, 'egresos'));
$totalCosto = array_sum(array_column($dias, 'costo'));
$utilidadNeta = $totalVentas - $totalCosto - $totalEgresos;

// ---------- Servicios completados en el periodo ----------
$stmt = $pdo->prepare("SELECT sv.nombre,
        SUM(isv.cantidad) cantidad,
        SUM(isv.precio_venta_aplicado) ingresos_generados,
        SUM(isv.costo_total_aplicado) costo
    FROM ingresos_servicios isv
    JOIN ingresos i ON i.id = isv.ingreso_id
    JOIN servicios sv ON sv.id = isv.servicio_id
    WHERE i.fecha BETWEEN ? AND ?
    GROUP BY sv.id
    ORDER BY cantidad DESC");
$stmt->execute([$fechaDesde, $fechaHasta]);
$serviciosCompletados = $stmt->fetchAll();

$pageTitle = 'Libro diario';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Libro diario</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>contabilidad/export.php?desde=<?= h($fechaDesde) ?>&hasta=<?= h($fechaHasta) ?>">Exportar a Excel</a>
    </div>
</div>

<div class="panel">
    <form method="get" class="form-grid">
        <div class="field">
            <label>Desde</label>
            <input type="date" name="desde" value="<?= h($fechaDesde) ?>">
        </div>
        <div class="field">
            <label>Hasta</label>
            <input type="date" name="hasta" value="<?= h($fechaHasta) ?>">
        </div>
        <div class="field" style="align-self:flex-end;">
            <button type="submit" class="btn">Filtrar</button>
        </div>
    </form>
</div>

<div class="summary-cards">
    <div class="card"><div class="label">Ventas (ingresos)</div><div class="value"><?= money($totalVentas) ?></div></div>
    <div class="card"><div class="label">Depositos (banco)</div><div class="value"><?= money($totalDepositos) ?></div></div>
    <div class="card"><div class="label">Egresos</div><div class="value"><?= money($totalEgresos) ?></div></div>
    <div class="card"><div class="label">Costo total</div><div class="value"><?= money($totalCosto) ?></div></div>
    <div class="card"><div class="label">Utilidad neta</div><div class="value" style="color: <?= $utilidadNeta >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidadNeta) ?></div></div>
</div>

<div class="panel">
    <h2 class="mt-0">Movimientos por dia</h2>
    <p class="muted">Utilidad neta = ventas - costo de servicios - egresos. Los depositos son solo traslado de efectivo al banco, no afectan la utilidad.</p>
    <div class="table-wrap" data-table>
    <table>
        <thead><tr><th>Fecha</th><th>Ventas</th><th>Depositos banco</th><th>Egresos</th><th>Costo</th><th>Utilidad</th></tr></thead>
        <tbody>
        <?php foreach ($dias as $d): $utilidadDia = $d['ventas'] - $d['costo'] - $d['egresos']; ?>
            <tr>
                <td><?= h($d['fecha']) ?></td>
                <td><?= money($d['ventas']) ?></td>
                <td><?= money($d['depositos']) ?></td>
                <td><?= money($d['egresos']) ?></td>
                <td><?= money($d['costo']) ?></td>
                <td style="color: <?= $utilidadDia >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidadDia) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$dias): ?><tr><td colspan="6" class="muted">Sin movimientos en el periodo seleccionado.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Servicios completados en el periodo</h2>
    <div class="table-wrap" data-table>
    <table>
        <thead><tr><th>Servicio</th><th>Cantidad realizada</th><th>Ingresos generados</th><th>Costo</th><th>Utilidad</th></tr></thead>
        <tbody>
        <?php foreach ($serviciosCompletados as $s): $utilidadServicio = $s['ingresos_generados'] - $s['costo']; ?>
            <tr>
                <td><?= h($s['nombre']) ?></td>
                <td><?= h($s['cantidad']) ?></td>
                <td><?= money($s['ingresos_generados']) ?></td>
                <td><?= money($s['costo']) ?></td>
                <td style="color: <?= $utilidadServicio >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidadServicio) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$serviciosCompletados): ?><tr><td colspan="5" class="muted">Sin servicios completados en el periodo seleccionado.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
