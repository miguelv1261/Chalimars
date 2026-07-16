<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$proveedor = ['id' => null, 'nombre' => '', 'contacto' => '', 'telefono' => '', 'email' => '', 'direccion' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM proveedores WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Proveedor no encontrado.', 'error');
        redirect(BASE_URL . 'proveedores/index.php');
    }
    $proveedor = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $proveedor['nombre'] = trim($_POST['nombre'] ?? '');
    $proveedor['contacto'] = trim($_POST['contacto'] ?? '');
    $proveedor['telefono'] = trim($_POST['telefono'] ?? '');
    $proveedor['email'] = trim($_POST['email'] ?? '');
    $proveedor['direccion'] = trim($_POST['direccion'] ?? '');

    if ($proveedor['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE proveedores SET nombre=?, contacto=?, telefono=?, email=?, direccion=? WHERE id=?');
            $stmt->execute([$proveedor['nombre'], $proveedor['contacto'], $proveedor['telefono'], $proveedor['email'], $proveedor['direccion'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) VALUES (?,?,?,?,?)');
            $stmt->execute([$proveedor['nombre'], $proveedor['contacto'], $proveedor['telefono'], $proveedor['email'], $proveedor['direccion']]);
        }
        flash_set('Proveedor guardado correctamente.');
        redirect(BASE_URL . 'proveedores/index.php');
    }
}

$pageTitle = $id ? 'Editar proveedor' : 'Nuevo proveedor';
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
                <input type="text" name="nombre" value="<?= h($proveedor['nombre']) ?>" required>
            </div>
            <div class="field">
                <label>Persona de contacto</label>
                <input type="text" name="contacto" value="<?= h($proveedor['contacto']) ?>">
            </div>
            <div class="field">
                <label>Telefono</label>
                <input type="text" name="telefono" value="<?= h($proveedor['telefono']) ?>">
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?= h($proveedor['email']) ?>">
            </div>
            <div class="field full">
                <label>Direccion</label>
                <input type="text" name="direccion" value="<?= h($proveedor['direccion']) ?>">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>proveedores/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
