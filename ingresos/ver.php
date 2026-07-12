<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM ingresos WHERE id = ?');
$stmt->execute([$id]);
$ingreso = $stmt->fetch();
if (!$ingreso) {
    flash_set('Ingreso no encontrado.', 'error');
    redirect(BASE_URL . 'ingresos/index.php');
}

$stmt = $pdo->prepare("SELECT ic.*, p.nombre AS producto_nombre, p.unidad AS producto_unidad,
        s.nombre AS servicio_nombre, g.nombre AS gasto_nombre
    FROM ingresos_costos ic
    LEFT JOIN productos p ON p.id = ic.producto_id
    LEFT JOIN servicios s ON s.id = ic.servicio_id
    LEFT JOIN gastos_indirectos g ON g.id = ic.gasto_indirecto_id
    WHERE ic.ingreso_id = ?
    ORDER BY ic.id");
$stmt->execute([$id]);
$costos = $stmt->fetchAll();

$totalCosto = array_sum(array_column($costos, 'costo_total'));
$utilidad = $ingreso['monto'] - $totalCosto;

$productos = $pdo->query('SELECT * FROM productos WHERE activo = 1 ORDER BY nombre')->fetchAll();
$servicios = $pdo->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre')->fetchAll();
$gastos = $pdo->query('SELECT * FROM gastos_indirectos WHERE activo = 1 ORDER BY nombre')->fetchAll();

$pageTitle = 'Detalle de ingreso';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Ingreso #<?= (int)$ingreso['id'] ?> - <?= h($ingreso['descripcion']) ?></h1>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>ingresos/index.php">Volver</a>
</div>

<div class="summary-cards">
    <div class="card"><div class="label">Fecha</div><div class="value" style="font-size:16px;"><?= h($ingreso['fecha']) ?></div></div>
    <div class="card"><div class="label">Cliente</div><div class="value" style="font-size:16px;"><?= h($ingreso['cliente'] ?: '-') ?></div></div>
    <div class="card"><div class="label">Monto del ingreso</div><div class="value"><?= money($ingreso['monto']) ?></div></div>
    <div class="card"><div class="label">Costo total</div><div class="value"><?= money($totalCosto) ?></div></div>
    <div class="card"><div class="label">Utilidad</div><div class="value" style="color: <?= $utilidad >= 0 ? '#2e7d32' : '#c62828' ?>;"><?= money($utilidad) ?></div></div>
</div>

<?php if ($ingreso['factura_pdf']): ?>
<p><a class="btn btn-sm" href="<?= BASE_URL ?>uploads/facturas/<?= h($ingreso['factura_pdf']) ?>" target="_blank">Ver factura PDF</a></p>
<?php endif; ?>

<div class="panel">
    <h2 class="mt-0">Detalle de costos</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tipo</th><th>Concepto</th><th>Cantidad</th><th>Costo unitario</th><th>Costo total</th><?php if (is_admin()): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($costos as $c): ?>
            <tr>
                <td>
                    <?php if ($c['tipo_costo'] === 'material'): ?><span class="tag tag-material">Material</span>
                    <?php elseif ($c['tipo_costo'] === 'mano_obra'): ?><span class="tag tag-mano_obra">Mano de obra</span>
                    <?php else: ?><span class="tag tag-gasto_indirecto">Gasto indirecto</span><?php endif; ?>
                </td>
                <td><?= h($c['producto_nombre'] ?? $c['servicio_nombre'] ?? $c['gasto_nombre']) ?></td>
                <td><?= h($c['cantidad']) ?></td>
                <td><?= money($c['costo_unitario']) ?></td>
                <td><?= money($c['costo_total']) ?></td>
                <?php if (is_admin()): ?>
                <td>
                    <form class="inline" method="post" action="<?= BASE_URL ?>ingresos/delete_costo.php" onsubmit="return confirm('Eliminar esta linea de costo? Si es material se repondra el stock.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="ingreso_id" value="<?= (int)$ingreso['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$costos): ?><tr><td colspan="6" class="muted">Sin costos registrados todavia.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Agregar costo</h2>
    <form method="post" action="<?= BASE_URL ?>ingresos/costos.php" id="costo-form">
        <?= csrf_field() ?>
        <input type="hidden" name="ingreso_id" value="<?= (int)$ingreso['id'] ?>">
        <div class="form-grid">
            <div class="field">
                <label>Tipo de costo</label>
                <select name="tipo_costo" id="tipo_costo" onchange="mostrarBloque(this.value)">
                    <option value="material">Material (inventario)</option>
                    <option value="mano_obra">Mano de obra / servicio</option>
                    <option value="gasto_indirecto">Gasto indirecto</option>
                </select>
            </div>
            <div class="field" id="bloque_cantidad">
                <label>Cantidad</label>
                <input type="number" step="0.01" min="0.01" name="cantidad" value="1" required>
            </div>
        </div>
        <div class="form-grid" id="bloque_material">
            <div class="field full">
                <label>Producto</label>
                <select name="producto_id">
                    <option value="">-- Seleccione un producto --</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" data-costo="<?= h($p['costo_unitario']) ?>">
                            <?= h($p['nombre']) ?> (stock: <?= h($p['stock']) ?> <?= h($p['unidad']) ?> - costo <?= money($p['costo_unitario']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid" id="bloque_mano_obra" style="display:none;">
            <div class="field full">
                <label>Servicio</label>
                <select name="servicio_id">
                    <option value="">-- Seleccione un servicio --</option>
                    <?php foreach ($servicios as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= h($s['nombre']) ?> (costo <?= money($s['costo_mano_obra']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid" id="bloque_gasto_indirecto" style="display:none;">
            <div class="field full">
                <label>Gasto indirecto</label>
                <select name="gasto_indirecto_id">
                    <option value="">-- Seleccione un gasto indirecto --</option>
                    <?php foreach ($gastos as $g): ?>
                        <option value="<?= (int)$g['id'] ?>"><?= h($g['nombre']) ?> (costo <?= money($g['costo_unitario']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Agregar costo</button>
        </div>
    </form>
</div>

<script>
function mostrarBloque(tipo) {
    document.getElementById('bloque_material').style.display = tipo === 'material' ? '' : 'none';
    document.getElementById('bloque_mano_obra').style.display = tipo === 'mano_obra' ? '' : 'none';
    document.getElementById('bloque_gasto_indirecto').style.display = tipo === 'gasto_indirecto' ? '' : 'none';
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
