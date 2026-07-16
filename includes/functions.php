<?php
// Funciones auxiliares generales

function money($amount) {
    return '$' . number_format((float)$amount, 2);
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
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
 * Aplica la receta de costeo de un servicio a un ingreso: por cada linea
 * de servicios_costos crea la linea correspondiente en ingresos_costos,
 * descontando stock de inventario cuando corresponde. Todo o nada:
 * si falta stock de algun material, revierte y lanza RuntimeException.
 */
function aplicar_servicio_a_ingreso(PDO $pdo, int $servicioId, int $ingresoId, float $cantidadAplicaciones, int $usuarioId) {
    $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ?');
    $stmt->execute([$servicioId]);
    $servicio = $stmt->fetch();
    if (!$servicio) {
        throw new RuntimeException('Servicio no encontrado.');
    }

    $stmt = $pdo->prepare('SELECT * FROM servicios_costos WHERE servicio_id = ?');
    $stmt->execute([$servicioId]);
    $lineas = $stmt->fetchAll();

    $pdo->beginTransaction();
    try {
        foreach ($lineas as $linea) {
            $cantidad = round($linea['cantidad'] * $cantidadAplicaciones, 2);

            if ($linea['tipo_costo'] === 'material') {
                $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? FOR UPDATE');
                $stmt->execute([$linea['producto_id']]);
                $producto = $stmt->fetch();
                if (!$producto) {
                    throw new RuntimeException('Un material de la receta ya no existe.');
                }
                if ($producto['stock'] < $cantidad) {
                    throw new RuntimeException('Stock insuficiente de "' . $producto['nombre'] . '". Disponible: ' . $producto['stock'] . ' ' . $producto['unidad_uso']);
                }
                $costoUnitario = (float)$producto['costo_uso'];
                $costoTotal = round($cantidad * $costoUnitario, 2);

                $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, producto_id, origen_servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$ingresoId, 'material', $producto['id'], $servicioId, $cantidad, $costoUnitario, $costoTotal, $usuarioId]);

                $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?')->execute([$cantidad, $producto['id']]);

                $pdo->prepare('INSERT INTO productos_movimientos (producto_id, tipo, cantidad, costo_unitario, costo_total, motivo, ingreso_id, usuario_id) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$producto['id'], 'salida', $cantidad, $costoUnitario, $costoTotal, 'Uso en servicio "' . $servicio['nombre'] . '" - Ingreso #' . $ingresoId, $ingresoId, $usuarioId]);

            } elseif ($linea['tipo_costo'] === 'mano_obra') {
                $stmt = $pdo->prepare('SELECT * FROM mano_obra WHERE id = ?');
                $stmt->execute([$linea['mano_obra_id']]);
                $manoObra = $stmt->fetch();
                if (!$manoObra) {
                    throw new RuntimeException('Una mano de obra de la receta ya no existe.');
                }
                $costoUnitario = (float)$manoObra['costo'];
                $costoTotal = round($cantidad * $costoUnitario, 2);

                $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, mano_obra_id, origen_servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$ingresoId, 'mano_obra', $manoObra['id'], $servicioId, $cantidad, $costoUnitario, $costoTotal, $usuarioId]);

            } else {
                $stmt = $pdo->prepare('SELECT * FROM gastos_indirectos WHERE id = ?');
                $stmt->execute([$linea['gasto_indirecto_id']]);
                $gasto = $stmt->fetch();
                if (!$gasto) {
                    throw new RuntimeException('Un gasto indirecto de la receta ya no existe.');
                }
                $costoUnitario = (float)$gasto['costo_unitario'];
                $costoTotal = round($cantidad * $costoUnitario, 2);

                $pdo->prepare('INSERT INTO ingresos_costos (ingreso_id, tipo_costo, gasto_indirecto_id, origen_servicio_id, cantidad, costo_unitario, costo_total, creado_por) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$ingresoId, 'gasto_indirecto', $gasto['id'], $servicioId, $cantidad, $costoUnitario, $costoTotal, $usuarioId]);
            }
        }
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
