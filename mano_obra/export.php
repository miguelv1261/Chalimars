<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$manoObra = $pdo->query('SELECT * FROM mano_obra ORDER BY nombre')->fetchAll();

$rows = array_map(function ($m) {
    return [$m['nombre'], $m['descripcion'], $m['costo'], $m['activo'] ? 'Activo' : 'Inactivo'];
}, $manoObra);

export_csv('mano_obra.csv', ['Nombre', 'Descripcion', 'Costo', 'Estado'], $rows);
