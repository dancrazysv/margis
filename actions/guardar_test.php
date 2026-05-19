<?php
// actions/guardar_test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Crear respuesta simple
$response = [
    'status' => 'success',
    'message' => 'Prueba de guardado exitosa',
    'post_data' => $_POST
];

echo json_encode($response);
?>