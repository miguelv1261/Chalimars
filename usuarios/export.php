<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY nombre_completo')->fetchAll();

$rows = array_map(function ($u) {
    return [$u['nombre_completo'], $u['username'], $u['rol'], $u['activo'] ? 'Activo' : 'Inactivo'];
}, $usuarios);

export_csv('usuarios.csv', ['Nombre', 'Usuario', 'Rol', 'Estado'], $rows);
