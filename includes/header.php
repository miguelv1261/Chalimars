<?php
$user = current_user();
$sesionCaja = isset($pdo) ? caja_abierta($pdo) : null;
$config = isset($pdo) ? get_config($pdo) : ['nombre_negocio' => 'Chalimars', 'logo' => null, 'color_primario' => '#8a4b6b'];
$pageTitle = $pageTitle ?? 'Chalimars';

$currentDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$currentDir = basename($currentDir);
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');

function nav_active($section, $currentDir, $currentFile = null) {
    if ($section === '') {
        return $currentDir === '' && $currentFile === 'index.php';
    }
    return $currentDir === $section;
}

$navItems = [
    ['section' => '', 'url' => 'index.php', 'label' => 'Panel',
        'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 22V12h6v10"></path>'],
];
$navGroups = [
    'Caja' => [
        ['section' => 'caja', 'url' => 'caja/index.php', 'label' => 'Sesiones de caja',
            'icon' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>'],
        ['section' => 'ingresos', 'url' => 'ingresos/index.php', 'label' => 'Ingresos',
            'icon' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>'],
        ['section' => 'egresos', 'url' => 'egresos/index.php', 'label' => 'Egresos',
            'icon' => '<polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline>'],
        ['section' => 'depositos', 'url' => 'depositos/index.php', 'label' => 'Depositos',
            'icon' => '<circle cx="12" cy="12" r="10"></circle><polyline points="8 12 12 16 16 12"></polyline><line x1="12" y1="8" x2="12" y2="16"></line>'],
    ],
    'Costeo' => [
        ['section' => 'servicios', 'url' => 'servicios/index.php', 'label' => 'Servicios',
            'icon' => '<line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>'],
        ['section' => 'productos', 'url' => 'productos/index.php', 'label' => 'Inventario materiales',
            'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>'],
        ['section' => 'proveedores', 'url' => 'proveedores/index.php', 'label' => 'Proveedores',
            'icon' => '<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>'],
        ['section' => 'mano_obra', 'url' => 'mano_obra/index.php', 'label' => 'Mano de obra',
            'icon' => '<circle cx="6" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><line x1="20" y1="4" x2="8.12" y2="15.88"></line><line x1="14.47" y1="14.48" x2="20" y2="20"></line><line x1="8.12" y1="8.12" x2="12" y2="12"></line>'],
        ['section' => 'gastos_indirectos', 'url' => 'gastos_indirectos/index.php', 'label' => 'Gastos indirectos',
            'icon' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>'],
    ],
];
if (is_admin()) {
    $navGroups['Administracion'] = [
        ['section' => 'usuarios', 'url' => 'usuarios/index.php', 'label' => 'Usuarios',
            'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'],
        ['section' => 'configuracion', 'url' => 'configuracion/index.php', 'label' => 'Configuracion',
            'icon' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>'],
    ];
}

function render_nav_link($item, $currentDir, $currentFile) {
    $active = nav_active($item['section'], $currentDir, $currentFile);
    printf(
        '<a href="%s%s" class="%s"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg><span>%s</span></a>',
        BASE_URL, h($item['url']), $active ? 'active' : '', $item['icon'], h($item['label'])
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> - <?= h($config['nombre_negocio']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<style>
:root {
    --primary: <?= h($config['color_primario']) ?>;
    --primary-dark: <?= h(darken_hex($config['color_primario'], 0.22)) ?>;
    --primary-darker: <?= h(darken_hex($config['color_primario'], 0.38)) ?>;
}
</style>
</head>
<body>
<div class="app">
    <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle-input">
    <aside class="sidebar">
        <div class="brand">
            <?php if (!empty($config['logo'])): ?>
                <img src="<?= BASE_URL ?>uploads/branding/<?= h($config['logo']) ?>" alt="<?= h($config['nombre_negocio']) ?>" class="brand-logo">
            <?php endif; ?>
            <span><?= h($config['nombre_negocio']) ?></span>
        </div>
        <nav>
            <?php render_nav_link($navItems[0], $currentDir, $currentFile); ?>
            <?php foreach ($navGroups as $groupLabel => $items): ?>
                <div class="nav-group"><?= h($groupLabel) ?></div>
                <?php foreach ($items as $item): ?>
                    <?php render_nav_link($item, $currentDir, $currentFile); ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>
    <label for="sidebar-toggle" class="sidebar-backdrop"></label>
    <div class="main">
        <header class="topbar">
            <label for="sidebar-toggle" class="menu-toggle" aria-label="Abrir menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </label>
            <div class="caja-status">
                <?php if ($sesionCaja): ?>
                    <span class="badge badge-open">Caja abierta desde <?= h(date('d/m/Y H:i', strtotime($sesionCaja['fecha_apertura']))) ?></span>
                <?php else: ?>
                    <span class="badge badge-closed">Sin caja abierta</span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span class="user-avatar"><?= h(mb_substr($user['nombre_completo'] ?? '?', 0, 1)) ?></span>
                <span><?= h($user['nombre_completo'] ?? '') ?> <span class="muted">(<?= h($user['rol'] ?? '') ?>)</span></span>
                <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-secondary">Salir</a>
            </div>
        </header>
        <main class="content">
            <?php $flash = flash_get(); if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
            <?php endif; ?>
