<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    require_role(['admin']);
}

$ingreso = ['id' => null, 'fecha' => date('Y-m-d'), 'cliente' => '', 'descripcion' => '', 'monto' => '', 'numero_factura' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM ingresos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('Ingreso no encontrado.', 'error');
        redirect(BASE_URL . 'ingresos/index.php');
    }
    $ingreso = $found;
}

$servicios = $pdo->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre')->fetchAll();
$productosActivos = $pdo->query('SELECT * FROM productos WHERE activo = 1 ORDER BY nombre')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ingreso['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $ingreso['cliente'] = trim($_POST['cliente'] ?? '');
    $ingreso['descripcion'] = trim($_POST['descripcion'] ?? '');
    $ingreso['monto'] = (float)($_POST['monto'] ?? 0);
    $ingreso['numero_factura'] = trim($_POST['numero_factura'] ?? '');

    if ($ingreso['monto'] <= 0) {
        $errors[] = 'El monto debe ser mayor a cero.';
    }
    if ($ingreso['descripcion'] === '') {
        $errors[] = 'La descripcion es obligatoria.';
    }

    $pdfName = null;
    if (!$errors) {
        try {
            $pdfName = handle_upload('factura_pdf', UPLOAD_FACTURAS, ['pdf']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        if ($id) {
            if ($pdfName) {
                $stmt = $pdo->prepare('UPDATE ingresos SET fecha=?, cliente=?, descripcion=?, monto=?, numero_factura=?, factura_pdf=? WHERE id=?');
                $stmt->execute([$ingreso['fecha'], $ingreso['cliente'], $ingreso['descripcion'], $ingreso['monto'], $ingreso['numero_factura'], $pdfName, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE ingresos SET fecha=?, cliente=?, descripcion=?, monto=?, numero_factura=? WHERE id=?');
                $stmt->execute([$ingreso['fecha'], $ingreso['cliente'], $ingreso['descripcion'], $ingreso['monto'], $ingreso['numero_factura'], $id]);
            }
            flash_set('Ingreso actualizado correctamente.');
            redirect(BASE_URL . 'ingresos/ver.php?id=' . $id);
        } else {
            $servicioId = (int)($_POST['servicio_id'] ?? 0) ?: null;
            $cantidadServicio = (float)($_POST['cantidad_servicio'] ?? 1) ?: 1;
            $productoId = (int)($_POST['producto_id'] ?? 0) ?: null;
            $cantidadProducto = (float)($_POST['cantidad_producto'] ?? 1) ?: 1;

            $stmt = $pdo->prepare('INSERT INTO ingresos (fecha, cliente, descripcion, monto, numero_factura, factura_pdf, creado_por) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$ingreso['fecha'], $ingreso['cliente'], $ingreso['descripcion'], $ingreso['monto'], $ingreso['numero_factura'], $pdfName, current_user()['id']]);
            $newId = (int)$pdo->lastInsertId();

            $mensajes = [];
            if ($servicioId) {
                try {
                    aplicar_servicio_a_ingreso($pdo, $servicioId, $newId, $cantidadServicio, current_user()['id']);
                    $mensajes[] = 'el servicio seleccionado';
                } catch (RuntimeException $e) {
                    flash_set('Ingreso registrado, pero no se pudo aplicar el costeo del servicio: ' . $e->getMessage(), 'error');
                }
            }
            if ($productoId) {
                try {
                    aplicar_producto_a_ingreso($pdo, $productoId, $newId, $cantidadProducto, current_user()['id']);
                    $mensajes[] = 'el producto vendido';
                } catch (RuntimeException $e) {
                    flash_set('Ingreso registrado, pero no se pudo vender el producto: ' . $e->getMessage(), 'error');
                }
            }
            if ($mensajes) {
                flash_set('Ingreso registrado y costeado automaticamente con ' . implode(' y ', $mensajes) . '.');
            } elseif (!$servicioId && !$productoId) {
                flash_set('Ingreso registrado. Ahora puede aplicar un servicio o vender un producto para costearlo.');
            }
            redirect(BASE_URL . 'ingresos/ver.php?id=' . $newId);
        }
    }
}

$pageTitle = $id ? 'Editar ingreso' : 'Nuevo ingreso';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1><?= h($pageTitle) ?></h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <?php if (!$id): ?>
            <div class="field full">
                <label>Servicio prestado (opcional, autocompleta y costea automaticamente)</label>
                <select name="servicio_id" id="servicio_id" onchange="autocompletarServicio(this)">
                    <option value="">-- Sin servicio --</option>
                    <?php foreach ($servicios as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" data-precio="<?= h($s['precio_venta']) ?>" data-nombre="<?= h($s['nombre']) ?>">
                            <?= h($s['nombre']) ?> (<?= money($s['precio_venta']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Cantidad del servicio</label>
                <input type="number" step="1" min="1" name="cantidad_servicio" value="1">
            </div>
            <div class="field full">
                <label>Producto vendido (opcional, se descuenta del inventario)</label>
                <div class="searchable-select" id="ss-producto">
                    <input type="text" class="ss-input" placeholder="Buscar producto por nombre..." autocomplete="off">
                    <input type="hidden" name="producto_id" id="producto_id">
                    <div class="ss-panel"></div>
                </div>
            </div>
            <div class="field">
                <label>Cantidad del producto (unidades de uso)</label>
                <input type="number" step="0.01" min="0.01" name="cantidad_producto" id="cantidad_producto" value="1">
            </div>
            <?php endif; ?>
            <div class="field">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= h($ingreso['fecha']) ?>" required>
            </div>
            <div class="field">
                <label>Cliente</label>
                <input type="text" name="cliente" value="<?= h($ingreso['cliente']) ?>">
            </div>
            <div class="field full">
                <label>Descripcion del servicio / venta</label>
                <input type="text" name="descripcion" id="descripcion" value="<?= h($ingreso['descripcion']) ?>" required>
            </div>
            <div class="field">
                <label>Monto</label>
                <input type="number" step="0.01" min="0.01" name="monto" id="monto" value="<?= h($ingreso['monto']) ?>" required>
            </div>
            <div class="field">
                <label>Numero de factura</label>
                <input type="text" name="numero_factura" value="<?= h($ingreso['numero_factura']) ?>">
            </div>
            <div class="field full">
                <label>Factura en PDF <?= $id ? '(dejar en blanco para no cambiar)' : '' ?></label>
                <input type="file" name="factura_pdf" accept="application/pdf">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>ingresos/index.php">Cancelar</a>
        </div>
    </form>
    <script>
    function autocompletarServicio(select) {
        var opt = select.options[select.selectedIndex];
        var precio = opt.getAttribute('data-precio');
        var nombre = opt.getAttribute('data-nombre');
        if (!precio) { return; }
        var montoInput = document.getElementById('monto');
        var descInput = document.getElementById('descripcion');
        if (!montoInput.value || montoInput.value == '0') { montoInput.value = precio; }
        if (!descInput.value) { descInput.value = nombre; }
    }

    <?php if (!$id): ?>
    var productosParaVenta = <?= json_encode(array_map(function ($p) {
        return ['value' => (string)$p['id'], 'label' => $p['nombre'], 'meta' => 'precio ' . money($p['precio_venta_uso']) . ' - stock uso: ' . $p['stock_uso']];
    }, $productosActivos)) ?>;
    new SearchableSelect(document.getElementById('ss-producto'), productosParaVenta);

    function sugerirMontoProducto() {
        var productoId = document.getElementById('producto_id').value;
        if (!productoId) { return; }
        var producto = productosParaVenta.find(function (p) { return p.value === productoId; });
        if (!producto) { return; }
        var cantidad = parseFloat(document.getElementById('cantidad_producto').value) || 1;
        var montoInput = document.getElementById('monto');
        var precioMatch = producto.meta.match(/precio \$([\d.,]+)/);
        if (precioMatch && (!montoInput.value || montoInput.value == '0')) {
            montoInput.value = (parseFloat(precioMatch[1].replace(/,/g, '')) * cantidad).toFixed(2);
        }
    }
    document.getElementById('producto_id').addEventListener('change', sugerirMontoProducto);
    document.getElementById('cantidad_producto').addEventListener('input', sugerirMontoProducto);
    <?php endif; ?>
    </script>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
