<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT r.*, u.nombre_completo AS creado_por_nombre
    FROM requerimientos r
    JOIN usuarios u ON u.id = r.creado_por
    WHERE r.id = ?');
$stmt->execute([$id]);
$requerimiento = $stmt->fetch();
if (!$requerimiento) {
    flash_set('Requerimiento no encontrado.', 'error');
    redirect(BASE_URL . 'requerimientos/index.php');
}

$estadoLabel = [
    'pendiente' => 'Pendiente',
    'en_progreso' => 'En progreso',
    'completado' => 'Completado',
    'rechazado' => 'Rechazado',
];

$puedeEditar = is_dev() || ($requerimiento['creado_por'] == current_user()['id'] && $requerimiento['estado'] === 'pendiente');

$pageTitle = 'Requerimiento #' . $requerimiento['id'];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Requerimiento #<?= (int)$requerimiento['id'] ?> - <?= h($requerimiento['titulo']) ?></h1>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>requerimientos/index.php">Volver</a>
</div>

<div class="summary-cards">
    <div class="card"><div class="label">Estado</div><div class="value" style="font-size:16px;"><span class="tag tag-<?= h($requerimiento['estado']) ?>"><?= h($estadoLabel[$requerimiento['estado']] ?? $requerimiento['estado']) ?></span></div></div>
    <div class="card"><div class="label">Creado por</div><div class="value" style="font-size:16px;"><?= h($requerimiento['creado_por_nombre']) ?></div></div>
    <div class="card"><div class="label">Fecha</div><div class="value" style="font-size:16px;"><?= h(date('d/m/Y H:i', strtotime($requerimiento['created_at']))) ?></div></div>
</div>

<div class="panel">
    <h2 class="mt-0">Descripcion</h2>
    <p style="white-space: pre-wrap;"><?= h($requerimiento['descripcion']) ?></p>
    <?php if ($puedeEditar): ?>
        <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>requerimientos/form.php?id=<?= (int)$requerimiento['id'] ?>">Editar</a>
    <?php endif; ?>
</div>

<div class="panel">
    <h2 class="mt-0">Respuesta del desarrollador</h2>
    <?php if ($requerimiento['respuesta']): ?>
        <p style="white-space: pre-wrap;"><?= h($requerimiento['respuesta']) ?></p>
    <?php else: ?>
        <p class="muted">Sin respuesta todavia.</p>
    <?php endif; ?>
</div>

<?php if (is_dev()): ?>
<div class="panel">
    <h2 class="mt-0">Actualizar estado y respuesta</h2>
    <form method="post" action="<?= BASE_URL ?>requerimientos/actualizar.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$requerimiento['id'] ?>">
        <div class="form-grid">
            <div class="field">
                <label>Estado</label>
                <select name="estado">
                    <?php foreach ($estadoLabel as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= $requerimiento['estado'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field full">
                <label>Respuesta</label>
                <textarea name="respuesta" rows="4"><?= h($requerimiento['respuesta']) ?></textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($puedeEditar): ?>
<div class="panel">
    <form method="post" action="<?= BASE_URL ?>requerimientos/delete.php" onsubmit="return confirm('Eliminar este requerimiento?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$requerimiento['id'] ?>">
        <button type="submit" class="btn btn-danger">Eliminar requerimiento</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
