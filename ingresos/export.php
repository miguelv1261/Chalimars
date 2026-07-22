<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$ingresos = $pdo->query("SELECT i.*, COALESCE(c.total_costo,0) total_costo
    FROM ingresos i
    LEFT JOIN (SELECT ingreso_id, SUM(costo_total) total_costo FROM ingresos_costos GROUP BY ingreso_id) c
        ON c.ingreso_id = i.id
    ORDER BY i.fecha DESC, i.id DESC")->fetchAll();

$rows = array_map(function ($i) {
    $utilidad = $i['monto'] - $i['total_costo'];
    return [$i['fecha'], $i['cliente'], $i['descripcion'], $i['monto'], round($i['total_costo'], 2), round($utilidad, 2), $i['numero_factura']];
}, $ingresos);

export_csv('ingresos.csv', ['Fecha', 'Cliente', 'Descripcion', 'Monto', 'Costo total', 'Utilidad', 'No. factura'], $rows);
