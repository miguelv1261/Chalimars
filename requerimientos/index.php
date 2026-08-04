<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Requerimientos';
$requerimientos = $pdo->query("SELECT r.*, u.nombre_completo AS creado_por_nombre
    FROM requerimientos r
    JOIN usuarios u ON u.id = r.creado_por
    ORDER BY r.created_at DESC")->fetchAll();

$estadoLabel = [
    'pendiente' => 'Pendiente',
    'en_progreso' => 'En progreso',
    'completado' => 'Completado',
    'rechazado' => 'Rechazado',
];

require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Requerimientos</h1>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>requerimientos/export.php">Exportar a Excel</a>
        <a class="btn" href="<?= BASE_URL ?>requerimientos/form.php">Nuevo requerimiento</a>
    </div>
</div>
<p class="muted">Registra aqui pedidos de soporte o de programacion (cambios, correcciones, nuevas necesidades del sistema) para que el desarrollador los atienda.</p>

<div class="table-wrap" data-table>
<table>
    <thead>
    <tr><th>Titulo</th><th>Creado por</th><th data-filter>Estado</th><th>Fecha</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($requerimientos as $r): ?>
        <tr>
            <td><?= h($r['titulo']) ?></td>
            <td><?= h($r['creado_por_nombre']) ?></td>
            <td><span class="tag tag-<?= h($r['estado']) ?>"><?= h($estadoLabel[$r['estado']] ?? $r['estado']) ?></span></td>
            <td><?= h(date('d/m/Y', strtotime($r['created_at']))) ?></td>
            <td>
                <a class="btn-icon" href="<?= BASE_URL ?>requerimientos/ver.php?id=<?= (int)$r['id'] ?>" title="Ver detalle"><?= icon_svg('eye') ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$requerimientos): ?><tr><td colspan="5" class="muted">Sin requerimientos registrados todavia.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
