<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$proveedores = $pdo->query('SELECT * FROM proveedores ORDER BY nombre')->fetchAll();

$rows = array_map(function ($p) {
    return [$p['nombre'], $p['contacto'], $p['telefono'], $p['email'], $p['activo'] ? 'Activo' : 'Inactivo'];
}, $proveedores);

export_csv('proveedores.csv', ['Nombre', 'Contacto', 'Telefono', 'Email', 'Estado'], $rows);
