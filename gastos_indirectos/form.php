<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$gasto = ['id' => null, 'nombre' => '', 'descripcion' => '', 'costo_unitario' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gastos_indirectos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Gasto indirecto no encontrado.', 'error');
        redirect(BASE_URL . 'gastos_indirectos/index.php');
    }
    $gasto = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $gasto['nombre'] = trim($_POST['nombre'] ?? '');
    $gasto['descripcion'] = trim($_POST['descripcion'] ?? '');
    $gasto['costo_unitario'] = (float)($_POST['costo_unitario'] ?? 0);

    if ($gasto['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($gasto['costo_unitario'] < 0) {
        $errors[] = 'El costo no puede ser negativo.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE gastos_indirectos SET nombre=?, descripcion=?, costo_unitario=? WHERE id=?');
            $stmt->execute([$gasto['nombre'], $gasto['descripcion'], $gasto['costo_unitario'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO gastos_indirectos (nombre, descripcion, costo_unitario) VALUES (?,?,?)');
            $stmt->execute([$gasto['nombre'], $gasto['descripcion'], $gasto['costo_unitario']]);
        }
        flash_set('Gasto indirecto guardado correctamente.');
        redirect(BASE_URL . 'gastos_indirectos/index.php');
    }
}

$pageTitle = $id ? 'Editar gasto indirecto' : 'Nuevo gasto indirecto';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Nombre (ej. Luz, Agua, Alquiler)</label>
                <input type="text" name="nombre" value="<?= h($gasto['nombre']) ?>" required>
            </div>
            <div class="field">
                <label>Costo unitario asignado</label>
                <input type="number" step="0.01" min="0" name="costo_unitario" value="<?= h($gasto['costo_unitario']) ?>" required>
            </div>
            <div class="field full">
                <label>Descripcion</label>
                <textarea name="descripcion" rows="3"><?= h($gasto['descripcion']) ?></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>gastos_indirectos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
