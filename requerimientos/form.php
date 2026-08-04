<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$requerimiento = ['id' => null, 'titulo' => '', 'descripcion' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM requerimientos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Requerimiento no encontrado.', 'error');
        redirect(BASE_URL . 'requerimientos/index.php');
    }
    // Solo el desarrollador, o quien lo creo mientras siga pendiente, puede editar el texto.
    $puedeEditar = is_dev() || ($found['creado_por'] == current_user()['id'] && $found['estado'] === 'pendiente');
    if (!$puedeEditar) {
        flash_set('Ya no se puede editar este requerimiento.', 'error');
        redirect(BASE_URL . 'requerimientos/ver.php?id=' . $id);
    }
    $requerimiento = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $requerimiento['titulo'] = trim($_POST['titulo'] ?? '');
    $requerimiento['descripcion'] = trim($_POST['descripcion'] ?? '');

    if ($requerimiento['titulo'] === '' || $requerimiento['descripcion'] === '') {
        $errors[] = 'El titulo y la descripcion son obligatorios.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE requerimientos SET titulo=?, descripcion=? WHERE id=?');
            $stmt->execute([$requerimiento['titulo'], $requerimiento['descripcion'], $id]);
            flash_set('Requerimiento actualizado correctamente.');
            redirect(BASE_URL . 'requerimientos/ver.php?id=' . $id);
        } else {
            $stmt = $pdo->prepare('INSERT INTO requerimientos (titulo, descripcion, creado_por) VALUES (?,?,?)');
            $stmt->execute([$requerimiento['titulo'], $requerimiento['descripcion'], current_user()['id']]);
            $newId = (int)$pdo->lastInsertId();
            flash_set('Requerimiento enviado correctamente.');
            redirect(BASE_URL . 'requerimientos/ver.php?id=' . $newId);
        }
    }
}

$pageTitle = $id ? 'Editar requerimiento' : 'Nuevo requerimiento';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Titulo</label>
                <input type="text" name="titulo" value="<?= h($requerimiento['titulo']) ?>" required>
            </div>
            <div class="field full">
                <label>Descripcion (que necesitas, para que sirve, y cualquier detalle util)</label>
                <textarea name="descripcion" rows="6" required><?= h($requerimiento['descripcion']) ?></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>requerimientos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
