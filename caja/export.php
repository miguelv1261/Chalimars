<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$sesiones = $pdo->query("SELECT cs.*, ua.nombre_completo AS abierta_por_nombre
    FROM cajas_sesiones cs
    JOIN usuarios ua ON ua.id = cs.abierta_por
    ORDER BY cs.id DESC")->fetchAll();

$rows = array_map(function ($s) {
    return [
        $s['id'], $s['fecha_apertura'], $s['fecha_cierre'] ?: '-',
        $s['monto_apertura'], $s['monto_contado'] ?? '-', $s['diferencia'] ?? '-',
        $s['estado'] === 'abierta' ? 'Abierta' : 'Cerrada', $s['abierta_por_nombre'],
    ];
}, $sesiones);

export_csv('sesiones_caja.csv', ['#', 'Apertura', 'Cierre', 'Monto apertura', 'Monto contado', 'Diferencia', 'Estado', 'Abierta por'], $rows);
