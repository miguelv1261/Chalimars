<?php
// Funciones auxiliares generales

function money($amount) {
    return '$' . number_format((float)$amount, 2);
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve el contenido interno (paths) de un icono SVG (estilo feather),
 * para usar dentro de botones de accion compactos en tablas.
 */
function icon_paths($name) {
    $icons = [
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
        'edit' => '<path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>',
        'history' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'plus-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line>',
        'power' => '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line>',
        'trash' => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
    ];
    return $icons[$name] ?? '';
}

/**
 * Devuelve el <svg> completo de un icono, para usar dentro de un
 * <a class="btn-icon" title="..."> o <button class="btn-icon" title="...">.
 */
function icon_svg($name) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . icon_paths($name) . '</svg>';
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        die('Token de seguridad invalido. Recargue la pagina e intente de nuevo.');
    }
}

function flash_set($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Devuelve la fila unica de configuracion (marca: nombre, logo, color).
 * Se cachea en memoria para no repetir la consulta en la misma peticion.
 */
function get_config(PDO $pdo) {
    static $config = null;
    if ($config === null) {
        $stmt = $pdo->query('SELECT * FROM configuracion WHERE id = 1');
        $config = $stmt->fetch() ?: [
            'nombre_negocio' => 'Chalimars',
            'logo' => null,
            'color_primario' => '#8a4b6b',
        ];
    }
    return $config;
}

/**
 * Oscurece un color hexadecimal (#rrggbb) en el porcentaje indicado (0-1).
 * Se usa para derivar el tono "hover/dark" del color primario configurable.
 */
function darken_hex($hex, $percent = 0.15) {
    $hex = ltrim((string)$hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return '#6d3a54';
    }
    [$r, $g, $b] = array_map(function ($channel) use ($percent) {
        $value = hexdec($channel) * (1 - $percent);
        return str_pad(dechex((int)max(0, min(255, $value))), 2, '0', STR_PAD_LEFT);
    }, str_split($hex, 2));
    return "#{$r}{$g}{$b}";
}

/**
 * Calcula el costo de mano de obra de un servicio: un porcentaje fijo
 * (MANO_OBRA_PORCENTAJE) de su precio de venta. Ya no se elige de un
 * catalogo al armar la receta.
 */
function costo_mano_obra_servicio($precioVenta) {
    return round((float)$precioVenta * MANO_OBRA_PORCENTAJE, 2);
}

/**
 * Descuenta stock de un producto en unidades de uso, de forma atomica
 * (FOR UPDATE) y manteniendo el invariante stock_uso = stock_tangible *
 * rendimiento (stock_tangible es la fuente de verdad). Registra el
 * movimiento de salida en productos_movimientos. Debe llamarse dentro de
 * una transaccion ya abierta por el caller. Lanza RuntimeException si el
 * producto no existe o no hay stock suficiente. Retorna la fila del
 * producto (previa al descuento).
 */
function descontar_stock_producto(PDO $pdo, int $productoId, float $cantidadUso, string $motivo, ?int $ingresoId, int $usuarioId): array {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? FOR UPDATE');
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch();
    if (!$producto) {
        throw new RuntimeException('El producto ya no existe.');
    }
    if ($producto['stock_uso'] < $cantidadUso) {
        throw new RuntimeException('Stock insuficiente de "' . $producto['nombre'] . '". Disponible: ' . $producto['stock_uso']);
    }

    $nuevoStockUso = round($producto['stock_uso'] - $cantidadUso, 2);
    $nuevoStockTangible = $producto['rendimiento'] > 0
        ? round($producto['stock_tangible'] - ($cantidadUso / $producto['rendimiento']), 2)
        : $producto['stock_tangible'];
    $pdo->prepare('UPDATE productos SET stock_uso = ?, stock_tangible = ? WHERE id = ?')
        ->execute([$nuevoStockUso, $nuevoStockTangible, $productoId]);

    $costoUnitario = (float)$producto['costo_uso'];
    $costoTotal = round($cantidadUso * $costoUnitario, 2);
    $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, motivo, ingreso_id, usuario_id) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$productoId, 'salida', $cantidadUso, $costoUnitario, $costoTotal, $motivo, $ingresoId, $usuarioId]);

    return $producto;
}

/**
 * Inverso de descontar_stock_producto(): repone stock (entrada) manteniendo
 * el mismo invariante stock_uso/stock_tangible. Se usa al eliminar una
 * linea de costo o de venta de producto ya aplicada a un ingreso.
 */
function reponer_stock_producto(PDO $pdo, int $productoId, float $cantidadUso, string $motivo, ?int $ingresoId, int $usuarioId): void {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? FOR UPDATE');
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch();
    if (!$producto) {
        return;
    }

    $nuevoStockUso = round($producto['stock_uso'] + $cantidadUso, 2);
    $nuevoStockTangible = $producto['rendimiento'] > 0
        ? round($producto['stock_tangible'] + ($cantidadUso / $producto['rendimiento']), 2)
        : $producto['stock_tangible'];
    $pdo->prepare('UPDATE productos SET stock_uso = ?, stock_tangible = ? WHERE id = ?')
        ->execute([$nuevoStockUso, $nuevoStockTangible, $productoId]);

    $costoUnitario = (float)$producto['costo_uso'];
    $costoTotal = round($cantidadUso * $costoUnitario, 2);
    $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, motivo, ingreso_id, usuario_id) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$productoId, 'entrada', $cantidadUso, $costoUnitario, $costoTotal, $motivo, $ingresoId, $usuarioId]);
}

/**
 * Aplica la receta de costeo de un servicio a un ingreso: por cada linea
 * de materiales/gastos indirectos de servicios_costos crea la linea
 * correspondiente en ingresos_costos (descontando stock cuando aplica),
 * y agrega automaticamente una linea de mano de obra igual al 30% del
 * precio de venta del servicio. Ademas registra la aplicacion en
 * ingresos_servicios (para el libro diario). Todo o nada: si falta
 * stock de algun material, revierte todo y lanza RuntimeException.
 */
function aplicar_servicio_a_ingreso(PDO $pdo, int $servicioId, int $ingresoId, float $cantidadAplicaciones, int $usuarioId) {
    $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ?');
    $stmt->execute([$servicioId]);
    $servicio = $stmt->fetch();
    if (!$servicio) {
        throw new RuntimeException('Servicio no encontrado.');
    }

    $stmt = $pdo->prepare("SELECT * FROM servicios_costos WHERE servicio_id = ? AND tipo_costo != 'mano_obra'");
    $stmt->execute([$servicioId]);
    $lineas = $stmt->fetchAll();

    $pdo->beginTransaction();
    try {
        $costoTotalAplicado = 0;

        foreach ($lineas as $linea) {
            $cantidad = round($linea['cantidad'] * $cantidadAplicaciones, 2);

            if ($linea['tipo_costo'] === 'material') {
                $producto = descontar_stock_producto(
                    $pdo, (int)$linea['producto_id'], $cantidad,
                    'Uso en servicio "' . $servicio['nombre'] . '" - Ingreso #' . $ingresoId,
                    $ingresoId, $usuarioId
                );
                $costoUnitario = (float)$producto['costo_uso'];
                $costoTotal = round($cantidad * $costoUnitario, 2);
                $costoTotalAplicado += $costoTotal;

                $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, producto_id, origen_servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$ingresoId, 'material', $producto['id'], $servicioId, $cantidad, $costoUnitario, $costoTotal, $usuarioId]);

            } else {
                $stmt = $pdo->prepare('SELECT * FROM gastos_indirectos WHERE id = ?');
                $stmt->execute([$linea['gasto_indirecto_id']]);
                $gasto = $stmt->fetch();
                if (!$gasto) {
                    throw new RuntimeException('Un gasto indirecto de la receta ya no existe.');
                }
                $costoUnitario = (float)$gasto['costo_unitario'];
                $costoTotal = round($cantidad * $costoUnitario, 2);
                $costoTotalAplicado += $costoTotal;

                $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, gasto_indirecto_id, origen_servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$ingresoId, 'gasto_indirecto', $gasto['id'], $servicioId, $cantidad, $costoUnitario, $costoTotal, $usuarioId]);
            }
        }

        // Mano de obra: 30% del precio de venta del servicio (no viene de un catalogo)
        $costoManoObraUnitario = costo_mano_obra_servicio($servicio['precio_venta']);
        $costoManoObraTotal = round($costoManoObraUnitario * $cantidadAplicaciones, 2);
        $costoTotalAplicado += $costoManoObraTotal;

        $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, origen_servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?)')
            ->execute([$ingresoId, 'mano_obra', $servicioId, $cantidadAplicaciones, $costoManoObraUnitario, $costoManoObraTotal, $usuarioId]);

        $precioVentaAplicado = round((float)$servicio['precio_venta'] * $cantidadAplicaciones, 2);
        $pdo->prepare('INSERT INTO ingresos_servicios (ingreso_id, servicio_id, cantidad, precio_venta_aplicado, costo_total_aplicado, creado_por) VALUES (?,?,?,?,?,?)')
            ->execute([$ingresoId, $servicioId, $cantidadAplicaciones, $precioVentaAplicado, $costoTotalAplicado, $usuarioId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Vende un producto directamente en un ingreso (fuera de la receta de un
 * servicio, ej. venta de un shampoo en el mostrador): descuenta stock y
 * registra la linea en ingresos_productos con el precio de venta y el
 * costo aplicados en ese momento. Todo o nada: si no hay stock suficiente,
 * revierte todo y lanza RuntimeException.
 */
function aplicar_producto_a_ingreso(PDO $pdo, int $productoId, int $ingresoId, float $cantidad, int $usuarioId) {
    $pdo->beginTransaction();
    try {
        $producto = descontar_stock_producto(
            $pdo, $productoId, $cantidad,
            'Venta directa - Ingreso #' . $ingresoId,
            $ingresoId, $usuarioId
        );

        $costoUnitario = (float)$producto['costo_uso'];
        $costoTotal = round($cantidad * $costoUnitario, 2);
        $precioUnitario = (float)$producto['precio_venta_uso'];
        $subtotal = round($cantidad * $precioUnitario, 2);

        $pdo->prepare('INSERT INTO ingresos_productos (ingreso_id, producto_id, cantidad, precio_unitario_aplicado, costo_unitario_aplicado, subtotal_aplicado, costo_total_aplicado, creado_por) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$ingresoId, $productoId, $cantidad, $precioUnitario, $costoUnitario, $subtotal, $costoTotal, $usuarioId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Sube un archivo verificando extension y tamano permitidos.
 * Retorna el nombre de archivo generado (sin ruta) o null si no se envio archivo.
 * Lanza RuntimeException si el archivo es invalido.
 */
function handle_upload($inputName, $destDir, array $allowedExt = ['pdf', 'jpg', 'jpeg', 'png']) {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo.');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('El archivo excede el tamano maximo permitido (5MB).');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Tipo de archivo no permitido. Se aceptan: ' . implode(', ', $allowedExt));
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('El contenido del archivo no coincide con un tipo permitido.');
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $destDir . $newName)) {
        throw new RuntimeException('No se pudo guardar el archivo subido.');
    }
    return $newName;
}

/**
 * Envia $rows como un archivo CSV descargable (BOM UTF-8 y separador ";"
 * para que Excel en espanol reconozca acentos y columnas) y termina la
 * ejecucion. $headers es la fila de encabezado; cada elemento de $rows es
 * un array indexado con los mismos valores/orden que $headers.
 */
function export_csv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}
