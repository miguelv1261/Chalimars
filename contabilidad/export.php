<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$hoy = date('Y-m-d');
$fechaDesde = $_GET['desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['hasta'] ?? $hoy;
if ($fechaDesde > $fechaHasta) {
    [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
}

$dias = [];
function libro_diario_acumular_export(&$dias, $filas, $campo) {
    foreach ($filas as $fila) {
        $fecha = $fila['fecha'];
        if (!isset($dias[$fecha])) {
            $dias[$fecha] = ['fecha' => $fecha, 'ventas' => 0, 'depositos' => 0, 'egresos' => 0, 'costo' => 0];
        }
        $dias[$fecha][$campo] = (float)$fila['total'];
    }
}

$stmt = $pdo->prepare('SELECT fecha, SUM(monto) total FROM ingresos WHERE fecha BETWEEN ? AND ? GROUP BY fecha');
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular_export($dias, $stmt->fetchAll(), 'ventas');

$stmt = $pdo->prepare('SELECT fecha, SUM(monto) total FROM depositos WHERE fecha BETWEEN ? AND ? GROUP BY fecha');
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular_export($dias, $stmt->fetchAll(), 'depositos');

$stmt = $pdo->prepare('SELECT fecha, SUM(monto) total FROM egresos WHERE fecha BETWEEN ? AND ? GROUP BY fecha');
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular_export($dias, $stmt->fetchAll(), 'egresos');

$stmt = $pdo->prepare("SELECT i.fecha, SUM(ic.costo_total) total
    FROM ingresos_costos ic JOIN ingresos i ON i.id = ic.ingreso_id
    WHERE i.fecha BETWEEN ? AND ? GROUP BY i.fecha");
$stmt->execute([$fechaDesde, $fechaHasta]);
libro_diario_acumular_export($dias, $stmt->fetchAll(), 'costo');

krsort($dias);

$rows = array_map(function ($d) {
    $utilidad = $d['ventas'] - $d['costo'] - $d['egresos'];
    return [$d['fecha'], round($d['ventas'], 2), round($d['depositos'], 2), round($d['egresos'], 2), round($d['costo'], 2), round($utilidad, 2)];
}, $dias);

export_csv('libro_diario.csv', ['Fecha', 'Ventas', 'Depositos banco', 'Egresos', 'Costo', 'Utilidad'], $rows);
