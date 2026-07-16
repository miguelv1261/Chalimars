<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ?');
$stmt->execute([$id]);
$servicio = $stmt->fetch();
if (!$servicio) {
    flash_set('Servicio no encontrado.', 'error');
    redirect(BASE_URL . 'servicios/index.php');
}

$stmt = $pdo->prepare("
    SELECT sc.*,
        p.nombre AS producto_nombre, p.unidad_uso AS producto_unidad, p.costo_uso AS producto_costo,
        mo.nombre AS mano_obra_nombre, mo.costo AS mano_obra_costo,
        gi.nombre AS gasto_nombre, gi.costo_unitario AS gasto_costo
    FROM servicios_costos sc
    LEFT JOIN productos p ON p.id = sc.producto_id
    LEFT JOIN mano_obra mo ON mo.id = sc.mano_obra_id
    LEFT JOIN gastos_indirectos gi ON gi.id = sc.gasto_indirecto_id
    WHERE sc.servicio_id = ?
    ORDER BY sc.id
");
$stmt->execute([$id]);
$costos = $stmt->fetchAll();

$totalCosto = 0;
foreach ($costos as &$c) {
    $costoUnitario = $c['tipo_costo'] === 'material' ? $c['producto_costo']
        : ($c['tipo_costo'] === 'mano_obra' ? $c['mano_obra_costo'] : $c['gasto_costo']);
    $c['costo_unitario'] = (float)$costoUnitario;
    $c['costo_total'] = $c['costo_unitario'] * $c['cantidad'];
    $totalCosto += $c['costo_total'];
}
unset($c);

$utilidad = $servicio['precio_venta'] - $totalCosto;

$productos = $pdo->query('SELECT * FROM productos WHERE activo = 1 ORDER BY nombre')->fetchAll();
$manoObraCatalogo = $pdo->query('SELECT * FROM mano_obra WHERE activo = 1 ORDER BY nombre')->fetchAll();
$gastos = $pdo->query('SELECT * FROM gastos_indirectos WHERE activo = 1 ORDER BY nombre')->fetchAll();

$pageTitle = 'Receta de servicio';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1><?= h($servicio['nombre']) ?></h1>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>servicios/index.php">Volver</a>
</div>

<div class="summary-cards">
    <div class="card"><div class="label">Precio de venta</div><div class="value"><?= money($servicio['precio_venta']) ?></div></div>
    <div class="card"><div class="label">Costo de la receta</div><div class="value"><?= money($totalCosto) ?></div></div>
    <div class="card"><div class="label">Utilidad</div><div class="value" style="color: <?= $utilidad >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidad) ?></div></div>
</div>

<div class="panel">
    <h2 class="mt-0">Receta de costeo</h2>
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
                <td><?= h($c['producto_nombre'] ?? $c['mano_obra_nombre'] ?? $c['gasto_nombre']) ?></td>
                <td><?= h($c['cantidad']) ?><?= $c['tipo_costo'] === 'material' ? ' ' . h($c['producto_unidad']) : '' ?></td>
                <td><?= money($c['costo_unitario']) ?></td>
                <td><?= money($c['costo_total']) ?></td>
                <?php if (is_admin()): ?>
                <td>
                    <form class="inline" method="post" action="<?= BASE_URL ?>servicios/delete_costo.php" onsubmit="return confirm('Eliminar esta linea de la receta?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="servicio_id" value="<?= (int)$servicio['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$costos): ?><tr><td colspan="6" class="muted">Esta receta aun no tiene costos definidos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php if (is_admin()): ?>
<div class="panel">
    <h2 class="mt-0">Agregar a la receta</h2>
    <form method="post" action="<?= BASE_URL ?>servicios/costos.php" id="costo-form">
        <?= csrf_field() ?>
        <input type="hidden" name="servicio_id" value="<?= (int)$servicio['id'] ?>">
        <div class="form-grid">
            <div class="field">
                <label>Tipo de costo</label>
                <select name="tipo_costo" id="tipo_costo" onchange="mostrarBloque(this.value)">
                    <option value="material">Material (inventario)</option>
                    <option value="mano_obra">Mano de obra</option>
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
                        <option value="<?= (int)$p['id'] ?>">
                            <?= h($p['nombre']) ?> (costo por <?= h($p['unidad_uso']) ?>: <?= money($p['costo_uso']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid" id="bloque_mano_obra" style="display:none;">
            <div class="field full">
                <label>Mano de obra</label>
                <select name="mano_obra_id">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($manoObraCatalogo as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= h($m['nombre']) ?> (costo <?= money($m['costo']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid" id="bloque_gasto_indirecto" style="display:none;">
            <div class="field full">
                <label>Gasto indirecto</label>
                <select name="gasto_indirecto_id">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($gastos as $g): ?>
                        <option value="<?= (int)$g['id'] ?>"><?= h($g['nombre']) ?> (costo <?= money($g['costo_unitario']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Agregar a la receta</button>
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
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
