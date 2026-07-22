<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$egresos = $pdo->query('SELECT * FROM egresos ORDER BY fecha DESC, id DESC')->fetchAll();

$rows = array_map(function ($e) {
    return [$e['fecha'], $e['descripcion'], $e['tipo_documento'] === 'factura' ? 'Factura' : 'Nota de venta', $e['numero_documento'], $e['monto']];
}, $egresos);

export_csv('egresos.csv', ['Fecha', 'Descripcion', 'Tipo documento', 'No. documento', 'Monto'], $rows);
