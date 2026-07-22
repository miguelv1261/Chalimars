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

$stmt = $pdo->prepare("SELECT ic.*, p.nombre AS producto_nombre,
        mo.nombre AS mano_obra_nombre, g.nombre AS gasto_nombre, sv.nombre AS origen_servicio_nombre
    FROM ingresos_costos ic
    LEFT JOIN productos p ON p.id = ic.producto_id
    LEFT JOIN mano_obra mo ON mo.id = ic.mano_obra_id
    LEFT JOIN gastos_indirectos g ON g.id = ic.gasto_indirecto_id
    LEFT JOIN servicios sv ON sv.id = ic.origen_servicio_id
    WHERE ic.ingreso_id = ?
    ORDER BY ic.id");
$stmt->execute([$id]);
$costos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT ip.*, p.nombre AS producto_nombre
    FROM ingresos_productos ip
    JOIN productos p ON p.id = ip.producto_id
    WHERE ip.ingreso_id = ?
    ORDER BY ip.id");
$stmt->execute([$id]);
$productosVendidos = $stmt->fetchAll();

$totalCosto = array_sum(array_column($costos, 'costo_total')) + array_sum(array_column($productosVendidos, 'costo_total_aplicado'));
$utilidad = $ingreso['monto'] - $totalCosto;

$servicios = $pdo->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre')->fetchAll();
$productosActivos = $pdo->query('SELECT * FROM productos WHERE activo = 1 ORDER BY nombre')->fetchAll();

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
    <div class="card"><div class="label">Utilidad</div><div class="value" style="color: <?= $utilidad >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidad) ?></div></div>
</div>

<?php if ($ingreso['factura_pdf']): ?>
<p><a class="btn btn-sm" href="<?= BASE_URL ?>uploads/facturas/<?= h($ingreso['factura_pdf']) ?>" target="_blank">Ver factura PDF</a></p>
<?php endif; ?>

<div class="panel">
    <h2 class="mt-0">Detalle de costos</h2>
    <div class="table-wrap" data-table>
    <table>
        <thead><tr><th>Tipo</th><th>Concepto</th><th>Servicio de origen</th><th>Cantidad</th><th>Costo unitario</th><th>Costo total</th><?php if (is_admin()): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($costos as $c): ?>
            <tr>
                <td>
                    <?php if ($c['tipo_costo'] === 'material'): ?><span class="tag tag-material">Material</span>
                    <?php elseif ($c['tipo_costo'] === 'mano_obra'): ?><span class="tag tag-mano_obra">Mano de obra</span>
                    <?php else: ?><span class="tag tag-gasto_indirecto">Gasto indirecto</span><?php endif; ?>
                </td>
                <td><?= h($c['producto_nombre'] ?? $c['mano_obra_nombre'] ?? $c['gasto_nombre'] ?? ($c['tipo_costo'] === 'mano_obra' ? 'Mano de obra (' . (MANO_OBRA_PORCENTAJE * 100) . '%)' : '')) ?></td>
                <td><?= h($c['origen_servicio_nombre'] ?? '-') ?></td>
                <td><?= h($c['cantidad']) ?></td>
                <td><?= money($c['costo_unitario']) ?></td>
                <td><?= money($c['costo_total']) ?></td>
                <?php if (is_admin()): ?>
                <td>
                    <form class="inline" method="post" action="<?= BASE_URL ?>ingresos/delete_costo.php" onsubmit="return confirm('Eliminar esta linea de costo? Si es material se repondra el stock.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="ingreso_id" value="<?= (int)$ingreso['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon-danger" title="Eliminar"><?= icon_svg('trash') ?></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$costos): ?><tr><td colspan="7" class="muted">Sin costos registrados todavia.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Productos vendidos</h2>
    <div class="table-wrap" data-table>
    <table>
        <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th><th>Costo</th><?php if (is_admin()): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($productosVendidos as $pv): ?>
            <tr>
                <td><?= h($pv['producto_nombre']) ?></td>
                <td><?= h($pv['cantidad']) ?></td>
                <td><?= money($pv['precio_unitario_aplicado']) ?></td>
                <td><?= money($pv['subtotal_aplicado']) ?></td>
                <td><?= money($pv['costo_total_aplicado']) ?></td>
                <?php if (is_admin()): ?>
                <td>
                    <form class="inline" method="post" action="<?= BASE_URL ?>ingresos/delete_producto.php" onsubmit="return confirm('Eliminar esta venta de producto? Se repondra el stock.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$pv['id'] ?>">
                        <input type="hidden" name="ingreso_id" value="<?= (int)$ingreso['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon-danger" title="Eliminar"><?= icon_svg('trash') ?></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$productosVendidos): ?><tr><td colspan="6" class="muted">Sin productos vendidos todavia.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <h2 class="mt-0">Aplicar un servicio</h2>
    <p class="muted">Selecciona un servicio ya definido (con su receta de materiales, mano de obra y gastos indirectos) y se costeara automaticamente.</p>
    <form method="post" action="<?= BASE_URL ?>ingresos/aplicar_servicio.php">
        <?= csrf_field() ?>
        <input type="hidden" name="ingreso_id" value="<?= (int)$ingreso['id'] ?>">
        <div class="form-grid">
            <div class="field full">
                <label>Servicio</label>
                <select name="servicio_id" required>
                    <option value="">-- Seleccione un servicio --</option>
                    <?php foreach ($servicios as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= h($s['nombre']) ?> (<?= money($s['precio_venta']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Cantidad</label>
                <input type="number" step="1" min="1" name="cantidad" value="1" required>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Aplicar servicio</button>
            <?php if (!$servicios): ?><span class="muted">No hay servicios activos. <a href="<?= BASE_URL ?>servicios/form.php">Crear uno</a>.</span><?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <h2 class="mt-0">Vender un producto</h2>
    <p class="muted">Vende un producto directamente (sin pasar por la receta de un servicio): descuenta stock y suma su costo a este ingreso.</p>
    <form method="post" action="<?= BASE_URL ?>ingresos/aplicar_producto.php">
        <?= csrf_field() ?>
        <input type="hidden" name="ingreso_id" value="<?= (int)$ingreso['id'] ?>">
        <div class="form-grid">
            <div class="field full">
                <label>Producto</label>
                <div class="searchable-select" id="ss-producto-venta">
                    <input type="text" class="ss-input" placeholder="Buscar producto por nombre..." autocomplete="off">
                    <input type="hidden" name="producto_id">
                    <div class="ss-panel"></div>
                </div>
            </div>
            <div class="field">
                <label>Cantidad (unidades de uso)</label>
                <input type="number" step="0.01" min="0.01" name="cantidad" value="1" required>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Vender producto</button>
            <?php if (!$productosActivos): ?><span class="muted">No hay productos activos. <a href="<?= BASE_URL ?>productos/form.php">Crear uno</a>.</span><?php endif; ?>
        </div>
    </form>
</div>

<script>
new SearchableSelect(document.getElementById('ss-producto-venta'), <?= json_encode(array_map(function ($p) {
    return ['value' => (string)$p['id'], 'label' => $p['nombre'], 'meta' => 'precio ' . money($p['precio_venta_uso']) . ' - stock uso: ' . $p['stock_uso']];
}, $productosActivos)) ?>);
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
