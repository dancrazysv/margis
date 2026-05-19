<?php
// actions/guardar_debug.php - PARA DEPURACIÓN
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');

echo json_encode([
    'status' => 'debug',
    'message' => 'El archivo se está ejecutando',
    'post_data' => $_POST
]);
exit;
?>