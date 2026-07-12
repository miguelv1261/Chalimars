<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if (caja_abierta($pdo)) {
    flash_set('Ya existe una caja abierta.', 'error');
    redirect(BASE_URL . 'caja/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $monto = (float)($_POST['monto_apertura'] ?? 0);
    $notas = trim($_POST['notas'] ?? '');

    if ($monto < 0) {
        $errors[] = 'El monto de apertura no puede ser negativo.';
    }

    if (!$errors) {
        if (caja_abierta($pdo)) {
            $errors[] = 'Ya existe una caja abierta.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO cajas_sesiones (fecha_apertura, monto_apertura, estado, abierta_por, notas) VALUES (NOW(), ?, "abierta", ?, ?)');
            $stmt->execute([$monto, current_user()['id'], $notas]);
            flash_set('Caja abierta correctamente.');
            redirect(BASE_URL . 'index.php');
        }
    }
}

$pageTitle = 'Abrir caja';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1>Abrir caja</h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Monto de apertura (efectivo inicial)</label>
                <input type="number" step="0.01" min="0" name="monto_apertura" value="0" required>
            </div>
            <div class="field full">
                <label>Notas (opcional)</label>
                <textarea name="notas" rows="2"></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Abrir caja</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
