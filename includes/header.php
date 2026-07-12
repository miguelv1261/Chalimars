<?php
$user = current_user();
$sesionCaja = isset($pdo) ? caja_abierta($pdo) : null;
$pageTitle = $pageTitle ?? 'Chalimars';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> - Chalimars</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">Chalimars</div>
        <nav>
            <a href="<?= BASE_URL ?>index.php">Panel</a>
            <div class="nav-group">Caja</div>
            <a href="<?= BASE_URL ?>caja/index.php">Sesiones de caja</a>
            <a href="<?= BASE_URL ?>ingresos/index.php">Ingresos</a>
            <a href="<?= BASE_URL ?>egresos/index.php">Egresos</a>
            <a href="<?= BASE_URL ?>depositos/index.php">Depositos</a>
            <div class="nav-group">Catalogos</div>
            <a href="<?= BASE_URL ?>productos/index.php">Inventario materiales</a>
            <a href="<?= BASE_URL ?>servicios/index.php">Mano de obra / Servicios</a>
            <a href="<?= BASE_URL ?>gastos_indirectos/index.php">Gastos indirectos</a>
            <?php if (is_admin()): ?>
            <div class="nav-group">Administracion</div>
            <a href="<?= BASE_URL ?>usuarios/index.php">Usuarios</a>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="caja-status">
                <?php if ($sesionCaja): ?>
                    <span class="badge badge-open">Caja abierta desde <?= h(date('d/m/Y H:i', strtotime($sesionCaja['fecha_apertura']))) ?></span>
                <?php else: ?>
                    <span class="badge badge-closed">Sin caja abierta</span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <?= h($user['nombre_completo'] ?? '') ?> (<?= h($user['rol'] ?? '') ?>)
                &middot; <a href="<?= BASE_URL ?>logout.php">Cerrar sesion</a>
            </div>
        </header>
        <main class="content">
            <?php $flash = flash_get(); if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
            <?php endif; ?>
