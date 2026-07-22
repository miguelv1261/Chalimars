<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$depositos = $pdo->query('SELECT * FROM depositos ORDER BY fecha DESC, id DESC')->fetchAll();

$rows = array_map(function ($d) {
    return [$d['fecha'], $d['banco'], $d['numero_referencia'], $d['monto']];
}, $depositos);

export_csv('depositos.csv', ['Fecha', 'Banco', 'Referencia', 'Monto'], $rows);
