<?php
// actions/enviar_revision.php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $iniciales = $_SESSION['iniciales'];

    // Solo se puede enviar si el estado es DIGITADO u OBSERVADO
    // Y verificamos que el registro pertenezca al usuario (opcional según tu lógica)
    $sql = "UPDATE margi SET estado = 'EN_REVISION' 
            WHERE id = ? AND estado IN ('DIGITADO', 'OBSERVADO')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el registro o ya está en revisión.']);
    }
}