<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$gastos = $pdo->query('SELECT * FROM gastos_indirectos ORDER BY nombre')->fetchAll();

$rows = array_map(function ($g) {
    return [$g['nombre'], $g['descripcion'], $g['costo_unitario'], $g['activo'] ? 'Activo' : 'Inactivo'];
}, $gastos);

export_csv('gastos_indirectos.csv', ['Nombre', 'Descripcion', 'Costo unitario', 'Estado'], $rows);
