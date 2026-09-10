<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Inventario de materiales';
$productos = $pdo->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();

$totalProductos = count($productos);
$totalActivos = 0;
$totalStockBajo = 0;
foreach ($productos as $p) {
    if ($p['activo']) {
        $totalActivos++;
        if ($p['stock_uso'] <= $p['stock_minimo']) {
            $totalStockBajo++;
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Inventario de materiales</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>productos/export.php">Exportar a Excel</a>
        <?php if (is_admin()): ?>
            <a class="btn" href="<?= BASE_URL ?>productos/form.php">Nuevo producto</a>
        <?php endif; ?>
    </div>
</div>
<p class="muted">El costeo se calcula por unidad de uso: precio de compra &divide; rendimiento = costo por uso (ej. shampoo de $15 con 20 usos = $0.75 por uso).</p>

<div class="summary-cards">
    <div class="card">
        <div class="label">Productos registrados</div>
        <div class="value"><?= $totalProductos ?></div>
    </div>
    <div class="card">
        <div class="label">Activos</div>
        <div class="value"><?= $totalActivos ?></div>
    </div>
    <div class="card<?= $totalStockBajo > 0 ? ' card-alert' : '' ?>">
        <div class="label">Con stock bajo</div>
        <div class="value"><?= $totalStockBajo ?></div>
    </div>
</div>

<div class="chip-group" id="stock-chip-filters">
    <button type="button" class="chip active" data-chip="reset">Todos</button>
    <button type="button" class="chip" data-filter-col="4" data-filter-value="bajo">Stock bajo</button>
    <button type="button" class="chip" data-filter-col="5" data-filter-value="activo">Activos</button>
    <button type="button" class="chip" data-filter-col="5" data-filter-value="inactivo">Inactivos</button>
</div>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr>
        <th>Codigo</th>
        <th>Producto</th>
        <th>Stock</th>
        <th>Precio venta</th>
        <th class="col-hidden-filter" data-filter>Nivel</th>
        <th data-filter>Estado</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
        <?php
            $stockBajo = $p['stock_uso'] <= $p['stock_minimo'];
            $tooltipStock = 'Tangible: ' . $p['stock_tangible'] . ' · Minimo: ' . $p['stock_minimo'];
            $tooltipPrecio = 'Compra: ' . money($p['precio_compra']) . ' · Rinde ' . $p['rendimiento'] . ' usos · Costo por uso: ' . money($p['costo_uso']);
        ?>
        <tr>
            <td><?= h($p['codigo']) ?></td>
            <td><?= h($p['nombre']) ?></td>
            <td title="<?= h($tooltipStock) ?>">
                <span class="tag <?= $stockBajo ? 'tag-rechazado' : 'tag-completado' ?>"><?= $stockBajo ? 'Stock bajo' : 'Stock OK' ?></span>
                <div><strong><?= h($p['stock_uso']) ?></strong> <span class="muted">uso</span></div>
            </td>
            <td title="<?= h($tooltipPrecio) ?>"><?= money($p['precio_venta_uso']) ?></td>
            <td class="col-hidden-filter"><?= $stockBajo ? 'Bajo' : 'OK' ?></td>
            <td><span class="tag <?= $p['activo'] ? 'tag-completado' : 'tag-rechazado' ?>"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
            <td>
                <div class="action-icons">
                    <a class="btn-icon" href="<?= BASE_URL ?>productos/movimientos.php?id=<?= (int)$p['id'] ?>" title="Ver movimientos y detalle completo"><?= icon_svg('history') ?></a>
                    <?php if (is_admin()): ?>
                        <a class="btn-icon" href="<?= BASE_URL ?>productos/entrada.php?id=<?= (int)$p['id'] ?>" title="Entrada de stock"><?= icon_svg('plus-circle') ?></a>
                        <a class="btn-icon" href="<?= BASE_URL ?>productos/form.php?id=<?= (int)$p['id'] ?>" title="Editar"><?= icon_svg('edit') ?></a>
                        <form class="inline" method="post" action="<?= BASE_URL ?>productos/delete.php" onsubmit="return confirm('Cambiar estado de este producto?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn-icon <?= $p['activo'] ? 'btn-icon-danger' : 'btn-icon-success' ?>" title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icon_svg('power') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$productos): ?><tr><td colspan="7" class="muted">Sin productos registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Se espera a DOMContentLoaded (y a que este script se cargue despues
    // de datatable.js) para que la barra de herramientas ya exista: la
    // columna "Nivel" solo existe para alimentar su <select> de filtro;
    // los chips de arriba ya cubren esa funcion, asi que se oculta el
    // select generado automaticamente por datatable.js para no duplicarla.
    document.querySelectorAll('.table-toolbar select[data-col]').forEach(function (select) {
        var firstOption = select.options[0];
        if (firstOption && firstOption.textContent.indexOf('Nivel') === 0) {
            select.style.display = 'none';
        }
    });

    var chips = document.querySelectorAll('#stock-chip-filters .chip');
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');

            if (chip.dataset.chip === 'reset') {
                document.querySelectorAll('.table-toolbar select[data-col]').forEach(function (select) {
                    select.value = '';
                    select.dispatchEvent(new Event('change'));
                });
                return;
            }

            var targetCol = chip.dataset.filterCol;
            var targetValue = chip.dataset.filterValue;
            document.querySelectorAll('.table-toolbar select[data-col]').forEach(function (select) {
                select.value = select.dataset.col === targetCol ? targetValue : '';
                select.dispatchEvent(new Event('change'));
            });
        });
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
