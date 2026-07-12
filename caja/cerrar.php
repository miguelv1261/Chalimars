<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$sesion = caja_abierta($pdo);
if (!$sesion) {
    flash_set('No hay ninguna caja abierta.', 'error');
    redirect(BASE_URL . 'caja/index.php');
}

function totales_sesion(PDO $pdo, $sesionId) {
    $ingresos = $pdo->prepare('SELECT COALESCE(SUM(monto),0) t FROM ingresos WHERE caja_sesion_id = ?');
    $ingresos->execute([$sesionId]);
    $egresos = $pdo->prepare('SELECT COALESCE(SUM(monto),0) t FROM egresos WHERE caja_sesion_id = ?');
    $egresos->execute([$sesionId]);
    $depositos = $pdo->prepare('SELECT COALESCE(SUM(monto),0) t FROM depositos WHERE caja_sesion_id = ?');
    $depositos->execute([$sesionId]);
    return [
        'ingresos' => (float)$ingresos->fetchColumn(),
        'egresos' => (float)$egresos->fetchColumn(),
        'depositos' => (float)$depositos->fetchColumn(),
    ];
}

$totales = totales_sesion($pdo, $sesion['id']);
$montoEsperado = $sesion['monto_apertura'] + $totales['ingresos'] - $totales['egresos'] - $totales['depositos'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $montoContado = (float)($_POST['monto_contado'] ?? 0);
    $notas = trim($_POST['notas'] ?? '');
    $diferencia = $montoContado - $montoEsperado;

    $stmt = $pdo->prepare('UPDATE cajas_sesiones SET fecha_cierre = NOW(), monto_contado = ?, monto_esperado = ?, diferencia = ?, estado = "cerrada", cerrada_por = ?, notas = CONCAT(COALESCE(notas,""), ?) WHERE id = ? AND estado = "abierta"');
    $stmt->execute([$montoContado, $montoEsperado, $diferencia, current_user()['id'], $notas ? "\n" . $notas : '', $sesion['id']]);

    if ($stmt->rowCount() === 0) {
        $errors[] = 'La caja ya fue cerrada por otro usuario.';
    } else {
        flash_set('Caja cerrada correctamente.');
        redirect(BASE_URL . 'caja/ver.php?id=' . $sesion['id']);
    }
}

$pageTitle = 'Cerrar caja';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1>Cerrar caja #<?= (int)$sesion['id'] ?></h1></div>

<div class="summary-cards">
    <div class="card"><div class="label">Monto apertura</div><div class="value"><?= money($sesion['monto_apertura']) ?></div></div>
    <div class="card"><div class="label">Ingresos</div><div class="value"><?= money($totales['ingresos']) ?></div></div>
    <div class="card"><div class="label">Egresos</div><div class="value"><?= money($totales['egresos']) ?></div></div>
    <div class="card"><div class="label">Depositos</div><div class="value"><?= money($totales['depositos']) ?></div></div>
    <div class="card"><div class="label">Monto esperado en caja</div><div class="value"><?= money($montoEsperado) ?></div></div>
</div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Monto contado en efectivo</label>
                <input type="number" step="0.01" min="0" name="monto_contado" required>
            </div>
            <div class="field full">
                <label>Notas (opcional)</label>
                <textarea name="notas" rows="2"></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Confirmar cierre</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>index.php">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
