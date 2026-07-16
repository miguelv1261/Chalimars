<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$manoObra = ['id' => null, 'nombre' => '', 'descripcion' => '', 'costo' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM mano_obra WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Registro no encontrado.', 'error');
        redirect(BASE_URL . 'mano_obra/index.php');
    }
    $manoObra = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $manoObra['nombre'] = trim($_POST['nombre'] ?? '');
    $manoObra['descripcion'] = trim($_POST['descripcion'] ?? '');
    $manoObra['costo'] = (float)($_POST['costo'] ?? 0);

    if ($manoObra['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($manoObra['costo'] < 0) {
        $errors[] = 'El costo no puede ser negativo.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE mano_obra SET nombre=?, descripcion=?, costo=? WHERE id=?');
            $stmt->execute([$manoObra['nombre'], $manoObra['descripcion'], $manoObra['costo'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO mano_obra (nombre, descripcion, costo) VALUES (?,?,?)');
            $stmt->execute([$manoObra['nombre'], $manoObra['descripcion'], $manoObra['costo']]);
        }
        flash_set('Tarifa guardada correctamente.');
        redirect(BASE_URL . 'mano_obra/index.php');
    }
}

$pageTitle = $id ? 'Editar tarifa' : 'Nueva tarifa';
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
                <input type="text" name="nombre" value="<?= h($manoObra['nombre']) ?>" required>
            </div>
            <div class="field">
                <label>Costo</label>
                <input type="number" step="0.01" min="0" name="costo" value="<?= h($manoObra['costo']) ?>" required>
            </div>
            <div class="field full">
                <label>Descripcion</label>
                <textarea name="descripcion" rows="3"><?= h($manoObra['descripcion']) ?></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>mano_obra/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
