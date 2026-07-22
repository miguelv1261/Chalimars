<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$id]);
$producto = $stmt->fetch();
if (!$producto) {
    flash_set('Producto no encontrado.', 'error');
    redirect(BASE_URL . 'productos/index.php');
}

$proveedores = $pdo->query('SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cantidadCompra = (float)($_POST['cantidad_compra'] ?? 0);
    $precioCompraUnitario = (float)($_POST['precio_compra_unitario'] ?? $producto['precio_compra']);
    $proveedorId = (int)($_POST['proveedor_id'] ?? 0) ?: null;
    $numeroDocumento = trim($_POST['numero_documento'] ?? '');
    $motivo = trim($_POST['motivo'] ?? 'Compra de mercaderia');

    if ($cantidadCompra <= 0) {
        $errors[] = 'La cantidad comprada debe ser mayor a cero.';
    }
    if ($precioCompraUnitario < 0) {
        $errors[] = 'El precio de compra no puede ser negativo.';
    }

    if (!$errors) {
        $costoUso = round($precioCompraUnitario / $producto['rendimiento'], 4);
        $nuevoStockTangible = round((float)$producto['stock_tangible'] + $cantidadCompra, 2);
        $nuevoStockUso = round($nuevoStockTangible * $producto['rendimiento'], 2);
        $cantidadUso = round($nuevoStockUso - (float)$producto['stock_uso'], 2);
        $costoTotal = round($cantidadUso * $costoUso, 2);

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE productos SET stock_tangible = ?, stock_uso = ?, precio_compra = ?, costo_uso = ? WHERE id = ?')
            ->execute([$nuevoStockTangible, $nuevoStockUso, $precioCompraUnitario, $costoUso, $id]);
        $pdo->prepare('INSERT INTO productos_movimientos
                (producto_id, tipo, cantidad, costo_unitario, costo_total, cantidad_compra, precio_compra_unitario, proveedor_id, numero_documento, motivo, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$id, 'entrada', $cantidadUso, $costoUso, $costoTotal, $cantidadCompra, $precioCompraUnitario, $proveedorId, $numeroDocumento, $motivo ?: 'Compra de mercaderia', current_user()['id']]);
        $pdo->commit();
        flash_set('Entrada de inventario registrada.');
        redirect(BASE_URL . 'productos/movimientos.php?id=' . $id);
    }
}

$pageTitle = 'Entrada de stock';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1>Entrada de stock: <?= h($producto['nombre']) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <p class="muted">Stock actual: <?= h($producto['stock_tangible']) ?> tangible / <?= h($producto['stock_uso']) ?> uso &middot; Rendimiento: 1 unidad = <?= h($producto['rendimiento']) ?> unidades de uso</p>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">
        <div class="form-grid">
            <div class="field">
                <label>Proveedor</label>
                <select name="proveedor_id">
                    <option value="">-- Sin especificar --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= (int)$prov['id'] ?>"><?= h($prov['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Numero de factura / documento</label>
                <input type="text" name="numero_documento">
            </div>
            <div class="field">
                <label>Cantidad comprada (unidades tangibles)</label>
                <input type="number" step="0.01" min="0.01" name="cantidad_compra" required>
            </div>
            <div class="field">
                <label>Precio de compra por unidad</label>
                <input type="number" step="0.01" min="0" name="precio_compra_unitario" value="<?= h($producto['precio_compra']) ?>" required>
            </div>
            <div class="field full">
                <label>Motivo</label>
                <input type="text" name="motivo" value="Compra de mercaderia">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Registrar entrada</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>productos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
