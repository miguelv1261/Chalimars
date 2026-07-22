<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$producto = [
    'id' => null, 'codigo' => '', 'nombre' => '',
    'rendimiento' => 1, 'precio_compra' => '', 'precio_venta_uso' => '',
    'stock_minimo' => 0, 'stock_tangible' => 0, 'stock_uso' => 0,
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Producto no encontrado.', 'error');
        redirect(BASE_URL . 'productos/index.php');
    }
    $producto = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $producto['nombre'] = trim($_POST['nombre'] ?? '');
    $producto['rendimiento'] = (float)($_POST['rendimiento'] ?? 1);
    $producto['precio_compra'] = (float)($_POST['precio_compra'] ?? 0);
    $producto['precio_venta_uso'] = (float)($_POST['precio_venta_uso'] ?? 0);
    $producto['stock_minimo'] = (float)($_POST['stock_minimo'] ?? 0);
    $stockInicial = (float)($_POST['stock_inicial'] ?? 0);

    if ($producto['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($producto['rendimiento'] <= 0) {
        $errors[] = 'El rendimiento debe ser mayor a cero (cuantas unidades de uso rinde una unidad).';
    }
    if ($producto['precio_compra'] < 0 || $producto['precio_venta_uso'] < 0 || $producto['stock_minimo'] < 0) {
        $errors[] = 'Los valores numericos no pueden ser negativos.';
    }

    if (!$errors) {
        $costoUso = round($producto['precio_compra'] / $producto['rendimiento'], 4);
        try {
            $pdo->beginTransaction();
            if ($id) {
                // stock_tangible nunca se toca aqui; stock_uso se recalcula
                // para mantenerse consistente si cambio el rendimiento.
                $stockUso = round((float)$producto['stock_tangible'] * $producto['rendimiento'], 2);
                $stmt = $pdo->prepare('UPDATE productos SET nombre=?, rendimiento=?, precio_compra=?, costo_uso=?, precio_venta_uso=?, stock_minimo=?, stock_uso=? WHERE id=?');
                $stmt->execute([$producto['nombre'], $producto['rendimiento'], $producto['precio_compra'], $costoUso, $producto['precio_venta_uso'], $producto['stock_minimo'], $stockUso, $id]);
            } else {
                $stockUso = round($stockInicial * $producto['rendimiento'], 2);
                $codigoTemporal = 'TMP-' . bin2hex(random_bytes(8));
                $stmt = $pdo->prepare('INSERT INTO productos (codigo, nombre, rendimiento, precio_compra, costo_uso, precio_venta_uso, stock_minimo, stock_tangible, stock_uso) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$codigoTemporal, $producto['nombre'], $producto['rendimiento'], $producto['precio_compra'], $costoUso, $producto['precio_venta_uso'], $producto['stock_minimo'], $stockInicial, $stockUso]);
                $id = (int)$pdo->lastInsertId();

                $producto['codigo'] = 'P' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                $pdo->prepare('UPDATE productos SET codigo = ? WHERE id = ?')->execute([$producto['codigo'], $id]);

                if ($stockInicial > 0) {
                    $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, cantidad_compra, precio_compra_unitario, motivo, usuario_id) VALUES (?,?,?,?,?,?,?,?,?)')
                        ->execute([$id, 'entrada', $stockUso, $costoUso, round($stockUso * $costoUso, 2), $stockInicial, $producto['precio_compra'], 'Stock inicial', current_user()['id']]);
                }
            }
            $pdo->commit();
            flash_set('Producto guardado correctamente.');
            redirect(BASE_URL . 'productos/index.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = ($e->getCode() == 23000) ? 'Ese codigo de producto ya existe.' : 'Error al guardar el producto.';
        }
    }
}

$pageTitle = $id ? 'Editar producto' : 'Nuevo producto';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <?php if ($id): ?>
            <div class="field">
                <label>Codigo</label>
                <input type="text" value="<?= h($producto['codigo']) ?>" disabled>
            </div>
            <div class="field">
                <label>Stock actual</label>
                <input type="text" value="<?= h($producto['stock_tangible']) ?> tangible / <?= h($producto['stock_uso']) ?> uso" disabled>
                <span class="muted">Para sumar stock use <a href="<?= BASE_URL ?>productos/entrada.php?id=<?= (int)$id ?>">Entrada de stock</a>.</span>
            </div>
            <?php else: ?>
            <div class="field">
                <label>Codigo</label>
                <input type="text" value="Se genera automaticamente al guardar" disabled>
            </div>
            <?php endif; ?>
            <div class="field">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= h($producto['nombre']) ?>" required>
            </div>
            <div class="field">
                <label>Precio de compra (por unidad)</label>
                <input type="number" step="0.01" min="0" id="precio_compra" name="precio_compra" value="<?= h($producto['precio_compra']) ?>" required>
            </div>
            <div class="field">
                <label>Precio de venta (por unidad de uso)</label>
                <input type="number" step="0.01" min="0" name="precio_venta_uso" value="<?= h($producto['precio_venta_uso']) ?>" required>
                <span class="muted">Precio al que se vende cada unidad de uso al aplicarlo directamente en un ingreso.</span>
            </div>
            <div class="field">
                <label>Rendimiento (unidades de uso por unidad)</label>
                <input type="number" step="0.01" min="0.01" id="rendimiento" name="rendimiento" value="<?= h($producto['rendimiento']) ?>" required>
                <span class="muted">Ej. 1 unidad comprada rinde 20 unidades de uso.</span>
            </div>
            <div class="field">
                <label>Stock minimo (unidades de uso, para alertas)</label>
                <input type="number" step="0.01" min="0" name="stock_minimo" value="<?= h($producto['stock_minimo']) ?>">
            </div>
            <?php if (!$id): ?>
            <div class="field">
                <label>Stock inicial (unidades tangibles)</label>
                <input type="number" step="0.01" min="0" id="stock_inicial" name="stock_inicial" value="0">
                <span class="muted">Stock de uso calculado: <strong><span id="stock_uso_calculado">0</span></strong> unidades de uso.</span>
            </div>
            <?php endif; ?>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>productos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php if (!$id): ?>
<script>
function recalcularStockUso() {
    var stockInicial = parseFloat(document.getElementById('stock_inicial').value) || 0;
    var rendimiento = parseFloat(document.getElementById('rendimiento').value) || 0;
    document.getElementById('stock_uso_calculado').textContent = (stockInicial * rendimiento).toFixed(2);
}
document.getElementById('stock_inicial').addEventListener('input', recalcularStockUso);
document.getElementById('rendimiento').addEventListener('input', recalcularStockUso);
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
