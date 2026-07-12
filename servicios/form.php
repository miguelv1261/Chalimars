<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$servicio = ['id' => null, 'nombre' => '', 'descripcion' => '', 'costo_mano_obra' => ''];
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
    $servicio['costo_mano_obra'] = (float)($_POST['costo_mano_obra'] ?? 0);

    if ($servicio['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($servicio['costo_mano_obra'] < 0) {
        $errors[] = 'El costo no puede ser negativo.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE servicios SET nombre=?, descripcion=?, costo_mano_obra=? WHERE id=?');
            $stmt->execute([$servicio['nombre'], $servicio['descripcion'], $servicio['costo_mano_obra'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO servicios (nombre, descripcion, costo_mano_obra) VALUES (?,?,?)');
            $stmt->execute([$servicio['nombre'], $servicio['descripcion'], $servicio['costo_mano_obra']]);
        }
        flash_set('Servicio guardado correctamente.');
        redirect(BASE_URL . 'servicios/index.php');
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
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= h($servicio['nombre']) ?>" required>
            </div>
            <div class="field">
                <label>Costo de mano de obra</label>
                <input type="number" step="0.01" min="0" name="costo_mano_obra" value="<?= h($servicio['costo_mano_obra']) ?>" required>
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
