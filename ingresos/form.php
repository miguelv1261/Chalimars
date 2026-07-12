<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    require_role(['admin']);
}

$ingreso = ['id' => null, 'fecha' => date('Y-m-d'), 'cliente' => '', 'descripcion' => '', 'monto' => '', 'numero_factura' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM ingresos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Ingreso no encontrado.', 'error');
        redirect(BASE_URL . 'ingresos/index.php');
    }
    $ingreso = $found;
} else {
    $sesion = require_caja_abierta($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ingreso['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $ingreso['cliente'] = trim($_POST['cliente'] ?? '');
    $ingreso['descripcion'] = trim($_POST['descripcion'] ?? '');
    $ingreso['monto'] = (float)($_POST['monto'] ?? 0);
    $ingreso['numero_factura'] = trim($_POST['numero_factura'] ?? '');

    if ($ingreso['monto'] <= 0) {
        $errors[] = 'El monto debe ser mayor a cero.';
    }
    if ($ingreso['descripcion'] === '') {
        $errors[] = 'La descripcion es obligatoria.';
    }

    $pdfName = null;
    if (!$errors) {
        try {
            $pdfName = handle_upload('factura_pdf', UPLOAD_FACTURAS, ['pdf']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        if ($id) {
            if ($pdfName) {
                $stmt = $pdo->prepare('UPDATE ingresos SET fecha=?, cliente=?, descripcion=?, monto=?, numero_factura=?, factura_pdf=? WHERE id=?');
                $stmt->execute([$ingreso['fecha'], $ingreso['cliente'], $ingreso['descripcion'], $ingreso['monto'], $ingreso['numero_factura'], $pdfName, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE ingresos SET fecha=?, cliente=?, descripcion=?, monto=?, numero_factura=? WHERE id=?');
                $stmt->execute([$ingreso['fecha'], $ingreso['cliente'], $ingreso['descripcion'], $ingreso['monto'], $ingreso['numero_factura'], $id]);
            }
            flash_set('Ingreso actualizado correctamente.');
            redirect(BASE_URL . 'ingresos/ver.php?id=' . $id);
        } else {
            $stmt = $pdo->prepare('INSERT INTO ingresos (fecha, cliente, descripcion, monto, numero_factura, factura_pdf, caja_sesion_id, creado_por) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$ingreso['fecha'], $ingreso['cliente'], $ingreso['descripcion'], $ingreso['monto'], $ingreso['numero_factura'], $pdfName, $sesion['id'], current_user()['id']]);
            $newId = (int)$pdo->lastInsertId();
            flash_set('Ingreso registrado. Ahora puede agregar el detalle de costos.');
            redirect(BASE_URL . 'ingresos/ver.php?id=' . $newId);
        }
    }
}

$pageTitle = $id ? 'Editar ingreso' : 'Nuevo ingreso';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= h($ingreso['fecha']) ?>" required>
            </div>
            <div class="field">
                <label>Cliente</label>
                <input type="text" name="cliente" value="<?= h($ingreso['cliente']) ?>">
            </div>
            <div class="field full">
                <label>Descripcion del servicio / venta</label>
                <input type="text" name="descripcion" value="<?= h($ingreso['descripcion']) ?>" required>
            </div>
            <div class="field">
                <label>Monto</label>
                <input type="number" step="0.01" min="0.01" name="monto" value="<?= h($ingreso['monto']) ?>" required>
            </div>
            <div class="field">
                <label>Numero de factura</label>
                <input type="text" name="numero_factura" value="<?= h($ingreso['numero_factura']) ?>">
            </div>
            <div class="field full">
                <label>Factura en PDF <?= $id ? '(dejar en blanco para no cambiar)' : '' ?></label>
                <input type="file" name="factura_pdf" accept="application/pdf">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>ingresos/index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
