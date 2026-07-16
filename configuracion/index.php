<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$config = get_config($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nombreNegocio = trim($_POST['nombre_negocio'] ?? '');
    $colorPrimario = trim($_POST['color_primario'] ?? '#8a4b6b');
    $quitarLogo = isset($_POST['quitar_logo']);

    if ($nombreNegocio === '') {
        $errors[] = 'El nombre del negocio es obligatorio.';
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorPrimario)) {
        $errors[] = 'El color primario debe ser un valor hexadecimal valido (ej. #8a4b6b).';
    }

    $logoNuevo = null;
    if (!$errors) {
        try {
            $logoNuevo = handle_upload('logo', UPLOAD_BRANDING, ['png', 'jpg', 'jpeg']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $logoFinal = $config['logo'];
        $logoAnterior = null;

        if ($logoNuevo) {
            $logoAnterior = $config['logo'];
            $logoFinal = $logoNuevo;
        } elseif ($quitarLogo) {
            $logoAnterior = $config['logo'];
            $logoFinal = null;
        }

        $stmt = $pdo->prepare('UPDATE configuracion SET nombre_negocio = ?, logo = ?, color_primario = ? WHERE id = 1');
        $stmt->execute([$nombreNegocio, $logoFinal, $colorPrimario]);

        if ($logoAnterior && $logoAnterior !== $logoFinal) {
            $rutaAnterior = UPLOAD_BRANDING . $logoAnterior;
            if (is_file($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        flash_set('Configuracion guardada correctamente.');
        redirect(BASE_URL . 'configuracion/index.php');
    }
}

$pageTitle = 'Configuracion';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h1>Configuracion del negocio</h1></div>

<div class="panel">
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label>Nombre del negocio</label>
                <input type="text" name="nombre_negocio" value="<?= h($config['nombre_negocio']) ?>" required>
            </div>
            <div class="field">
                <label>Color primario</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="color" name="color_primario" value="<?= h($config['color_primario']) ?>" style="width:56px; padding:2px; height:38px;">
                </div>
            </div>
            <div class="field full">
                <label>Logo (PNG o JPG, se muestra en el menu y en el inicio de sesion)</label>
                <?php if ($config['logo']): ?>
                    <div style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
                        <img src="<?= BASE_URL ?>uploads/branding/<?= h($config['logo']) ?>" alt="Logo actual" style="max-height:60px; max-width:200px; border:1px solid var(--border); border-radius:8px; padding:4px; background:#fff;">
                        <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                            <input type="checkbox" name="quitar_logo" value="1" style="width:auto;"> Quitar logo actual
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="logo" accept="image/png,image/jpeg">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Guardar cambios</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
