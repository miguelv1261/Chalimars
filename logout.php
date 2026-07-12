<?php
require_once __DIR__ . '/includes/bootstrap.php';
$_SESSION = [];
session_destroy();
redirect(BASE_URL . 'login.php');
