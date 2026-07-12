<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$usuario = ['id' => null, 'username' => '', 'nombre_completo' => '', 'rol' => 'cajero', 'activo' => 1];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Usuario no encontrado.', 'error');
        redirect(BASE_URL . 'usuarios/index.php');
    }
    $usuario = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $usuario['username'] = trim($_POST['username'] ?? '');
    $usuario['nombre_completo'] = trim($_POST['nombre_completo'] ?? '');
    $usuario['rol'] = in_array($_POST['rol'] ?? '', ['admin', 'cajero'], true) ? $_POST['rol'] : 'cajero';
    $password = $_POST['password'] ?? '';

    if ($usuario['username'] === '' || $usuario['nombre_completo'] === '') {
        $errors[] = 'Usuario y nombre completo son obligatorios.';
    }
    if (!$id && $password === '') {
        $errors[] = 'La contrasena es obligatoria para usuarios nuevos.';
    }

    if (!$errors) {
        try {
            if ($id) {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE usuarios SET username=?, nombre_completo=?, rol=?, password=? WHERE id=?');
                    $stmt->execute([$usuario['username'], $usuario['nombre_completo'], $usuario['rol'], password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE usuarios SET username=?, nombre_completo=?, rol=? WHERE id=?');
                    $stmt->execute([$usuario['username'], $usuario['nombre_completo'], $usuario['rol'], $id]);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES (?,?,?,?)');
                $stmt->execute([$usuario['username'], password_hash($password, PASSWORD_DEFAULT), $usuario['nombre_completo'], $usuario['rol']]);
            }
            flash_set('Usuario guardado correctamente.');
            redirect(BASE_URL . 'usuarios/index.php');
        } catch (PDOException $e) {
            $errors[] = ($e->getCode() == 23000) ? 'Ese nombre de usuario ya existe.' : 'Error al guardar el usuario.';
        }
    }
}

$pageTitle = $id ? 'Editar usuario' : 'Nuevo usuario';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Nombre completo</label>
                <input type="text" name="nombre_completo" value="<?= h($usuario['nombre_completo']) ?>" required>
            </div>
            <div class="field">
                <label>Usuario</label>
                <input type="text" name="username" value="<?= h($usuario['username']) ?>" required>
            </div>
            <div class="field">
                <label>Rol</label>
                <select name="rol">
                    <option value="cajero" <?= $usuario['rol'] === 'cajero' ? 'selected' : '' ?>>Cajero</option>
                    <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="field">
                <label>Contrasena <?= $id ? '(dejar en blanco para no cambiar)' : '' ?></label>
                <input type="password" name="password" autocomplete="new-password">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>usuarios/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
