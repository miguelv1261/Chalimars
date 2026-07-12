<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(BASE_URL . 'index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = ? AND activo = 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'nombre_completo' => $user['nombre_completo'],
            'rol' => $user['rol'],
        ];
        redirect(BASE_URL . 'index.php');
    } else {
        $error = 'Usuario o contrasena incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesion - Chalimars</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="auth-box">
    <h1>Chalimars - Control de caja</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Usuario</label>
            <input type="text" name="username" required autofocus>
        </div>
        <div class="field">
            <label>Contrasena</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Ingresar</button>
    </form>
</div>
</body>
</html>
