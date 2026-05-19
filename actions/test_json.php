<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$response = ['status' => 'success', 'message' => 'Conexión exitosa'];
echo json_encode($response);
?>