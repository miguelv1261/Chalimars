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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cantidad = (float)($_POST['cantidad'] ?? 0);
    $costoUnitario = (float)($_POST['costo_unitario'] ?? $producto['costo_unitario']);
    $motivo = trim($_POST['motivo'] ?? 'Compra de mercaderia');

    if ($cantidad <= 0) {
        $errors[] = 'La cantidad debe ser mayor a cero.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE productos SET stock = stock + ?, costo_unitario = ? WHERE id = ?')
            ->execute([$cantidad, $costoUnitario, $id]);
        $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, motivo, usuario_id) VALUES (?,?,?,?,?,?,?)')
            ->execute([$id, 'entrada', $cantidad, $costoUnitario, $cantidad * $costoUnitario, $motivo ?: 'Compra de mercaderia', current_user()['id']]);
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
    <p class="muted">Stock actual: <?= h($producto['stock']) ?> <?= h($producto['unidad']) ?></p>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">
        <div class="form-grid">
            <div class="field">
                <label>Cantidad a ingresar</label>
                <input type="number" step="0.01" min="0.01" name="cantidad" required>
            </div>
            <div class="field">
                <label>Costo unitario</label>
                <input type="number" step="0.01" min="0" name="costo_unitario" value="<?= h($producto['costo_unitario']) ?>" required>
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
