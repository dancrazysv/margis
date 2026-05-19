<?php
// admin/eliminar_combo.php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verificar permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_area'] !== 'ADMINISTRADOR') {
    echo json_encode(['status' => 'error', 'message' => 'Sin permisos']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    exit;
}

// Verificar si el combo existe
$check = $conn->prepare("SELECT id, nombre_combo FROM combos_marginaciones WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Combo no encontrado']);
    exit;
}

$combo = $result->fetch_assoc();

// Eliminar el combo (las plantillas asociadas se eliminan en cascada por FOREIGN KEY)
$delete = $conn->prepare("DELETE FROM combos_marginaciones WHERE id = ?");
$delete->bind_param("i", $id);

if ($delete->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Combo eliminado correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>