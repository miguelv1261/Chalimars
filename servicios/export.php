<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$servicios = $pdo->query("
    SELECT s.*, COALESCE(c.costo_total, 0) AS costo_receta
    FROM servicios s
    LEFT JOIN (
        SELECT sc.servicio_id,
            SUM(
                CASE sc.tipo_costo
                    WHEN 'material' THEN sc.cantidad * p.costo_uso
                    WHEN 'gasto_indirecto' THEN sc.cantidad * gi.costo_unitario
                END
            ) AS costo_total
        FROM servicios_costos sc
        LEFT JOIN productos p ON p.id = sc.producto_id
        LEFT JOIN gastos_indirectos gi ON gi.id = sc.gasto_indirecto_id
        WHERE sc.tipo_costo != 'mano_obra'
        GROUP BY sc.servicio_id
    ) c ON c.servicio_id = s.id
    ORDER BY s.nombre
")->fetchAll();

$rows = array_map(function ($s) {
    $costoTotal = $s['costo_receta'] + costo_mano_obra_servicio($s['precio_venta']);
    $utilidad = $s['precio_venta'] - $costoTotal;
    return [$s['nombre'], $s['precio_venta'], round($costoTotal, 2), round($utilidad, 2), $s['activo'] ? 'Activo' : 'Inactivo'];
}, $servicios);

export_csv('servicios.csv', ['Servicio', 'Precio de venta', 'Costo receta', 'Utilidad', 'Estado'], $rows);
