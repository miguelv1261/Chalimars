<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$servicio = ['id' => null, 'nombre' => '', 'descripcion' => '', 'precio_venta' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Servicio no encontrado.', 'error');
        redirect(BASE_URL . 'servicios/index.php');
    }
    $servicio = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $servicio['nombre'] = trim($_POST['nombre'] ?? '');
    $servicio['descripcion'] = trim($_POST['descripcion'] ?? '');
    $servicio['precio_venta'] = (float)($_POST['precio_venta'] ?? 0);

    if ($servicio['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($servicio['precio_venta'] < 0) {
        $errors[] = 'El precio de venta no puede ser negativo.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE servicios SET nombre=?, descripcion=?, precio_venta=? WHERE id=?');
            $stmt->execute([$servicio['nombre'], $servicio['descripcion'], $servicio['precio_venta'], $id]);
            flash_set('Servicio actualizado correctamente.');
            redirect(BASE_URL . 'servicios/ver.php?id=' . $id);
        } else {
            $stmt = $pdo->prepare('INSERT INTO servicios (nombre, descripcion, precio_venta) VALUES (?,?,?)');
            $stmt->execute([$servicio['nombre'], $servicio['descripcion'], $servicio['precio_venta']]);
            $newId = (int)$pdo->lastInsertId();
            flash_set('Servicio creado. Ahora agregue su receta de costeo.');
            redirect(BASE_URL . 'servicios/ver.php?id=' . $newId);
        }
    }
}

$pageTitle = $id ? 'Editar servicio' : 'Nuevo servicio';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Nombre del servicio</label>
                <input type="text" name="nombre" value="<?= h($servicio['nombre']) ?>" placeholder="Ej. Corte de Cabello" required>
            </div>
            <div class="field">
                <label>Precio de venta</label>
                <input type="number" step="0.01" min="0" name="precio_venta" value="<?= h($servicio['precio_venta']) ?>" required>
            </div>
            <div class="field full">
                <label>Descripcion</label>
                <textarea name="descripcion" rows="3"><?= h($servicio['descripcion']) ?></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>servicios/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
