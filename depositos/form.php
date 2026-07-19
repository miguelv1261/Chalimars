<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$errors = [];
$deposito = ['fecha' => date('Y-m-d'), 'monto' => '', 'banco' => '', 'numero_referencia' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $deposito['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $deposito['monto'] = (float)($_POST['monto'] ?? 0);
    $deposito['banco'] = trim($_POST['banco'] ?? '');
    $deposito['numero_referencia'] = trim($_POST['numero_referencia'] ?? '');

    if ($deposito['monto'] <= 0) {
        $errors[] = 'El monto debe ser mayor a cero.';
    }

    $archivo = null;
    if (!$errors) {
        try {
            $archivo = handle_upload('comprobante_archivo', UPLOAD_DEPOSITOS, ['pdf', 'jpg', 'jpeg', 'png']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO depositos (fecha, monto, banco, numero_referencia, comprobante_archivo, creado_por) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$deposito['fecha'], $deposito['monto'], $deposito['banco'], $deposito['numero_referencia'], $archivo, current_user()['id']]);
        flash_set('Deposito registrado correctamente.');
        redirect(BASE_URL . 'depositos/index.php');
    }
}

$pageTitle = 'Nuevo deposito';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1>Nuevo deposito</h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= h($deposito['fecha']) ?>" required>
            </div>
            <div class="field">
                <label>Monto</label>
                <input type="number" step="0.01" min="0.01" name="monto" value="<?= h($deposito['monto']) ?>" required>
            </div>
            <div class="field">
                <label>Banco</label>
                <input type="text" name="banco" value="<?= h($deposito['banco']) ?>">
            </div>
            <div class="field">
                <label>Numero de referencia</label>
                <input type="text" name="numero_referencia" value="<?= h($deposito['numero_referencia']) ?>">
            </div>
            <div class="field full">
                <label>Comprobante (PDF o imagen)</label>
                <input type="file" name="comprobante_archivo" accept="application/pdf,image/jpeg,image/png">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>depositos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
