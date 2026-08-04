<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

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

$rows = array_map(function ($r) use ($estadoLabel) {
    return [$r['titulo'], $r['descripcion'], $estadoLabel[$r['estado']] ?? $r['estado'], $r['respuesta'], $r['creado_por_nombre'], $r['created_at']];
}, $requerimientos);

export_csv('requerimientos.csv', ['Titulo', 'Descripcion', 'Estado', 'Respuesta', 'Creado por', 'Fecha'], $rows);
