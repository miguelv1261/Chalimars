<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$producto = ['id' => null, 'codigo' => '', 'nombre' => '', 'unidad' => 'unidad', 'costo_unitario' => '', 'stock_minimo' => 0];
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
    $producto['codigo'] = trim($_POST['codigo'] ?? '');
    $producto['nombre'] = trim($_POST['nombre'] ?? '');
    $producto['unidad'] = trim($_POST['unidad'] ?? 'unidad');
    $producto['costo_unitario'] = (float)($_POST['costo_unitario'] ?? 0);
    $producto['stock_minimo'] = (float)($_POST['stock_minimo'] ?? 0);
    $stockInicial = (float)($_POST['stock_inicial'] ?? 0);

    if ($producto['codigo'] === '' || $producto['nombre'] === '') {
        $errors[] = 'Codigo y nombre son obligatorios.';
    }
    if ($producto['costo_unitario'] < 0 || $producto['stock_minimo'] < 0) {
        $errors[] = 'Los valores numericos no pueden ser negativos.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            if ($id) {
                $stmt = $pdo->prepare('UPDATE productos SET codigo=?, nombre=?, unidad=?, costo_unitario=?, stock_minimo=? WHERE id=?');
                $stmt->execute([$producto['codigo'], $producto['nombre'], $producto['unidad'], $producto['costo_unitario'], $producto['stock_minimo'], $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO productos (codigo, nombre, unidad, costo_unitario, stock_minimo, stock) VALUES (?,?,?,?,?,0)');
                $stmt->execute([$producto['codigo'], $producto['nombre'], $producto['unidad'], $producto['costo_unitario'], $producto['stock_minimo']]);
                $id = (int)$pdo->lastInsertId();

                if ($stockInicial > 0) {
                    $pdo->prepare('UPDATE productos SET stock = ? WHERE id = ?')->execute([$stockInicial, $id]);
                    $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, motivo, usuario_id) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$id, 'entrada', $stockInicial, $producto['costo_unitario'], $stockInicial * $producto['costo_unitario'], 'Stock inicial', current_user()['id']]);
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
            <div class="field">
                <label>Codigo</label>
                <input type="text" name="codigo" value="<?= h($producto['codigo']) ?>" required>
            </div>
            <div class="field">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= h($producto['nombre']) ?>" required>
            </div>
            <div class="field">
                <label>Unidad de medida</label>
                <input type="text" name="unidad" value="<?= h($producto['unidad']) ?>" placeholder="unidad, ml, gr...">
            </div>
            <div class="field">
                <label>Costo unitario</label>
                <input type="number" step="0.01" min="0" name="costo_unitario" value="<?= h($producto['costo_unitario']) ?>" required>
            </div>
            <div class="field">
                <label>Stock minimo</label>
                <input type="number" step="0.01" min="0" name="stock_minimo" value="<?= h($producto['stock_minimo']) ?>">
            </div>
            <?php if (!$id): ?>
            <div class="field">
                <label>Stock inicial</label>
                <input type="number" step="0.01" min="0" name="stock_inicial" value="0">
            </div>
            <?php endif; ?>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>productos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
