<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$productos = $pdo->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();

$rows = array_map(function ($p) {
    return [
        $p['codigo'], $p['nombre'], $p['precio_compra'], $p['precio_venta_uso'],
        $p['rendimiento'], $p['costo_uso'], $p['stock_tangible'], $p['stock_uso'],
        $p['stock_minimo'], $p['activo'] ? 'Activo' : 'Inactivo',
    ];
}, $productos);

export_csv('productos.csv', ['Codigo', 'Nombre', 'Precio compra', 'Precio venta', 'Rendimiento', 'Costo por uso', 'Stock tangible', 'Stock uso', 'Stock minimo', 'Estado'], $rows);
