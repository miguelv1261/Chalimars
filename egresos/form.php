<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$sesion = require_caja_abierta($pdo);
$errors = [];
$egreso = ['fecha' => date('Y-m-d'), 'descripcion' => '', 'monto' => '', 'tipo_documento' => 'factura', 'numero_documento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $egreso['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $egreso['descripcion'] = trim($_POST['descripcion'] ?? '');
    $egreso['monto'] = (float)($_POST['monto'] ?? 0);
    $egreso['tipo_documento'] = in_array($_POST['tipo_documento'] ?? '', ['factura', 'nota_venta'], true) ? $_POST['tipo_documento'] : 'factura';
    $egreso['numero_documento'] = trim($_POST['numero_documento'] ?? '');

    if ($egreso['monto'] <= 0) {
        $errors[] = 'El monto debe ser mayor a cero.';
    }
    if ($egreso['descripcion'] === '') {
        $errors[] = 'La descripcion es obligatoria.';
    }

    $archivo = null;
    if (!$errors) {
        try {
            $archivo = handle_upload('documento_archivo', UPLOAD_EGRESOS, ['pdf', 'jpg', 'jpeg', 'png']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO egresos (fecha, descripcion, monto, tipo_documento, numero_documento, documento_archivo, caja_sesion_id, creado_por) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$egreso['fecha'], $egreso['descripcion'], $egreso['monto'], $egreso['tipo_documento'], $egreso['numero_documento'], $archivo, $sesion['id'], current_user()['id']]);
        flash_set('Egreso registrado correctamente.');
        redirect(BASE_URL . 'egresos/index.php');
    }
}

$pageTitle = 'Nuevo egreso';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1>Nuevo egreso</h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= h($egreso['fecha']) ?>" required>
            </div>
            <div class="field">
                <label>Monto</label>
                <input type="number" step="0.01" min="0.01" name="monto" value="<?= h($egreso['monto']) ?>" required>
            </div>
            <div class="field full">
                <label>Descripcion</label>
                <input type="text" name="descripcion" value="<?= h($egreso['descripcion']) ?>" required>
            </div>
            <div class="field">
                <label>Tipo de documento</label>
                <select name="tipo_documento">
                    <option value="factura" <?= $egreso['tipo_documento'] === 'factura' ? 'selected' : '' ?>>Factura</option>
                    <option value="nota_venta" <?= $egreso['tipo_documento'] === 'nota_venta' ? 'selected' : '' ?>>Nota de venta</option>
                </select>
            </div>
            <div class="field">
                <label>Numero de documento</label>
                <input type="text" name="numero_documento" value="<?= h($egreso['numero_documento']) ?>">
            </div>
            <div class="field full">
                <label>Adjuntar documento (PDF o imagen)</label>
                <input type="file" name="documento_archivo" accept="application/pdf,image/jpeg,image/png">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>egresos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
