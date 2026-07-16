<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Servicios';

$servicios = $pdo->query("
    SELECT s.*, COALESCE(c.costo_total, 0) AS costo_total
    FROM servicios s
    LEFT JOIN (
        SELECT sc.servicio_id,
            SUM(
                CASE sc.tipo_costo
                    WHEN 'material' THEN sc.cantidad * p.costo_uso
                    WHEN 'mano_obra' THEN sc.cantidad * mo.costo
                    WHEN 'gasto_indirecto' THEN sc.cantidad * gi.costo_unitario
                END
            ) AS costo_total
        FROM servicios_costos sc
        LEFT JOIN productos p ON p.id = sc.producto_id
        LEFT JOIN mano_obra mo ON mo.id = sc.mano_obra_id
        LEFT JOIN gastos_indirectos gi ON gi.id = sc.gasto_indirecto_id
        GROUP BY sc.servicio_id
    ) c ON c.servicio_id = s.id
    ORDER BY s.nombre
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Servicios</h1>
    <?php if (is_admin()): ?>
        <a class="btn" href="<?= BASE_URL ?>servicios/form.php">Nuevo servicio</a>
    <?php endif; ?>
</div>
<p class="muted">Cada servicio trae su receta de costeo predefinida (materiales, mano de obra, gastos indirectos). Al registrar un ingreso solo se selecciona el servicio.</p>

<div class="table-wrap">
<table>
    <thead>
    <tr><th>Servicio</th><th>Precio de venta</th><th>Costo receta</th><th>Utilidad</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($servicios as $s): $utilidad = $s['precio_venta'] - $s['costo_total']; ?>
        <tr>
            <td><?= h($s['nombre']) ?></td>
            <td><?= money($s['precio_venta']) ?></td>
            <td><?= money($s['costo_total']) ?></td>
            <td style="color: <?= $utilidad >= 0 ? '#1e7d3c' : '#c4293a' ?>;"><?= money($utilidad) ?></td>
            <td><?= $s['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td class="actions">
                <a class="btn btn-sm" href="<?= BASE_URL ?>servicios/ver.php?id=<?= (int)$s['id'] ?>">Ver receta</a>
                <?php if (is_admin()): ?>
                    <a class="btn btn-sm" href="<?= BASE_URL ?>servicios/form.php?id=<?= (int)$s['id'] ?>">Editar</a>
                    <form class="inline" method="post" action="<?= BASE_URL ?>servicios/delete.php" onsubmit="return confirm('Cambiar estado de este servicio?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><?= $s['activo'] ? 'Desactivar' : 'Activar' ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$servicios): ?><tr><td colspan="6" class="muted">Sin servicios registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
